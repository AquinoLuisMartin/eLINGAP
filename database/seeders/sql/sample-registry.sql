SET LOCAL lock_timeout = '10s';
SET LOCAL statement_timeout = '180s';
SET LOCAL synchronous_commit = on;
SET LOCAL TIME ZONE 'Asia/Manila';

DO $$
DECLARE
    codes text[] := ARRAY['BAG','BAL','BUE','BUL','CAM','CAT','CAY','CYS','GUY','LAL','MAS','MHP','MAN','PAR','POB','PBU','SGA','SJP','SVI','SCL','SCR','SIL','TBA','TUM'];
    program_codes text[] := ARRAY[
        'december-assistance',
        'lgu-age-90',
        'lgu-age-100',
        'national-social-pension',
        'local-social-pension',
        'october-assistance',
        'provincial-centenarian',
        'national-milestones',
        'tss'
    ];
    actor bigint;
    sequence_number bigint;
    relation record;
    invalid boolean;
    payout_at timestamptz := '2026-10-21 12:00:00+08';
BEGIN
    PERFORM pg_advisory_xact_lock(194701, 1);
    -- Lock in a fixed order so concurrent writers cannot invalidate acceptance checks.
    FOR relation IN SELECT oid, oid::regclass AS name FROM pg_class
        WHERE relnamespace = 'public'::regnamespace AND relkind = 'r' ORDER BY oid
    LOOP
        EXECUTE format('LOCK TABLE %s IN SHARE ROW EXCLUSIVE MODE', relation.name);
    END LOOP;

    IF EXISTS (SELECT FROM benefit_schedules WHERE reference_number LIKE 'SYN-SCH-2026-%')
        OR EXISTS (SELECT FROM applications WHERE application_number LIKE 'SYN-APP-%')
        OR EXISTS (SELECT FROM senior_citizens WHERE address = 'SYNTHETIC ADDRESS - NOT A RESIDENCE') THEN
        RAISE EXCEPTION 'Sample dataset or synthetic profiles already exists';
    END IF;
    IF (SELECT count(*) FROM barangays WHERE code = ANY(codes) AND is_active) <> 24 THEN
        RAISE EXCEPTION 'All 24 expected active barangays are required';
    END IF;
    IF (SELECT count(*) FROM programs WHERE benefit_code = ANY(program_codes)) <> 9 THEN
        RAISE EXCEPTION 'All 9 expected benefit programs are required';
    END IF;
    SELECT u.id INTO actor FROM users u JOIN roles r ON r.id = u.role_id
        WHERE r.name = 'ADMIN' AND u.is_active ORDER BY u.id LIMIT 1;
    IF actor IS NULL THEN
        RAISE EXCEPTION 'An existing active administrator is required';
    END IF;

    CREATE TEMP TABLE sample_original_rows (relation_id oid, row_tid tid, fingerprint text) ON COMMIT DROP;
    FOR relation IN SELECT oid, oid::regclass AS name FROM pg_class
        WHERE relnamespace = 'public'::regnamespace AND relkind = 'r' ORDER BY oid
    LOOP
        EXECUTE format('INSERT INTO sample_original_rows SELECT %s, ctid, md5(to_jsonb(t)::text) FROM %s t', relation.oid, relation.name);
    END LOOP;

    CREATE TEMP TABLE sample_people ON COMMIT DROP AS
    WITH years AS (
        SELECT year, CASE WHEN year < 2010 THEN 371 ELSE 370 END AS quantity,
            make_date(year, 1, 1) AS starts,
            CASE WHEN year = 2026 THEN DATE '2026-10-21' ELSE make_date(year, 12, 31) END AS ends
        FROM generate_series(2000, 2026) year
    ), registrations AS (
        SELECT row_number() OVER (ORDER BY year, ordinal)::int AS n,
            starts + floor((ordinal - 1)::numeric * (ends - starts) / (quantity - 1))::int AS registered_on
        FROM years CROSS JOIN LATERAL generate_series(1, quantity) ordinal
    ), dated AS (
        SELECT n, registered_on, b.id AS barangay_id,
            (registered_on - make_interval(years => 60 + n % 16))::date AS birth_date,
            extract(year FROM age(DATE '2026-10-21', (registered_on - make_interval(years => 60 + n % 16))::date))::int AS age_on_payout
        FROM registrations JOIN barangays b ON b.code = codes[1 + (n - 1) % 24]
    )
    SELECT dated.*,
        CASE
            WHEN age_on_payout >= 100 AND n % 3 = 0 THEN 'lgu-age-100'
            WHEN age_on_payout >= 100 AND n % 3 = 1 THEN 'provincial-centenarian'
            WHEN age_on_payout IN (80, 85, 90, 95, 100) AND n % 2 = 0 THEN 'national-milestones'
            WHEN age_on_payout >= 90 AND n % 3 = 0 THEN 'lgu-age-90'
            WHEN registered_on <= DATE '2026-06-30' AND n % 5 = 0 THEN 'december-assistance'
            WHEN n % 5 = 1 THEN 'national-social-pension'
            WHEN n % 5 = 2 THEN 'local-social-pension'
            WHEN n % 5 = 3 THEN 'october-assistance'
            ELSE 'tss'
        END AS benefit_code
    FROM dated;
    ALTER TABLE sample_people ADD PRIMARY KEY (n);

    ALTER TABLE sample_people
        ADD COLUMN program_id bigint,
        ADD COLUMN schedule_id bigint,
        ADD COLUMN period_key text,
        ADD COLUMN milestone_age smallint,
        ADD COLUMN amount numeric(12, 2),
        ADD COLUMN rule_snapshot jsonb;

    UPDATE sample_people sp SET
        program_id = pr.id,
        period_key = CASE sp.benefit_code
            WHEN 'december-assistance' THEN '2026'
            WHEN 'lgu-age-90' THEN 'AGE-90'
            WHEN 'lgu-age-100' THEN 'AGE-100'
            WHEN 'national-social-pension' THEN '2026-Q4'
            WHEN 'local-social-pension' THEN '2026-H2'
            WHEN 'october-assistance' THEN '2026'
            WHEN 'provincial-centenarian' THEN 'AGE-100'
            WHEN 'national-milestones' THEN 'AGE-' || sp.age_on_payout::text
            ELSE '2026-SAMPLE'
        END,
        milestone_age = CASE
            WHEN sp.benefit_code IN ('lgu-age-90') THEN 90
            WHEN sp.benefit_code IN ('lgu-age-100', 'provincial-centenarian') THEN 100
            WHEN sp.benefit_code = 'national-milestones' THEN sp.age_on_payout::smallint
            ELSE NULL
        END,
        amount = CASE
            WHEN sp.benefit_code = 'lgu-age-90' THEN 5000
            WHEN sp.benefit_code IN ('lgu-age-100', 'provincial-centenarian') THEN 10000
            WHEN sp.benefit_code = 'national-milestones' AND sp.age_on_payout = 100 THEN 100000
            WHEN sp.benefit_code = 'national-milestones' THEN 10000
            ELSE coalesce(pr.default_amount, 500)
        END,
        rule_snapshot = pr.benefit_rules::jsonb || jsonb_build_object('government_level', pr.government_level, 'program_name', pr.name)
    FROM programs pr
    WHERE pr.benefit_code = sp.benefit_code;

    INSERT INTO benefit_schedules (program_id, reference_number, period_key, eligibility_on, scheduled_at,
        location, batch_name, barangay_ids, amount, available_funds, rule_snapshot, status,
        created_by, approved_by, approved_at, closed_at, created_at, updated_at)
    SELECT sp.program_id,
        'SYN-SCH-2026-' || upper(sp.benefit_code),
        CASE WHEN sp.benefit_code = 'national-milestones' THEN 'MILESTONES' ELSE min(sp.period_key) END,
        DATE '2026-10-21', payout_at,
        'SYNTHETIC - Santa Maria', 'SYNTHETIC - ' || pr.name,
        (SELECT json_agg(id ORDER BY id) FROM barangays WHERE code = ANY(codes)),
        coalesce(pr.default_amount, 500), sum(sp.amount),
        (pr.benefit_rules::jsonb || jsonb_build_object('government_level', pr.government_level, 'program_name', pr.name))::json,
        'CLOSED', actor, actor, payout_at, payout_at, payout_at, payout_at
    FROM sample_people sp
    JOIN programs pr ON pr.id = sp.program_id
    GROUP BY pr.id, sp.program_id, sp.benefit_code
    ORDER BY sp.program_id;

    UPDATE sample_people sp SET schedule_id = bs.id
    FROM benefit_schedules bs
    WHERE bs.program_id = sp.program_id AND bs.reference_number = 'SYN-SCH-2026-' || upper(sp.benefit_code);

    -- Reserve global sequence values using the same issuance-year format as OscaIdNumber.
    ALTER TABLE sample_people ADD COLUMN osca_id_number text;
    FOR relation IN SELECT n, registered_on FROM sample_people ORDER BY n LOOP
        LOOP
            INSERT INTO osca_id_numbers (issued_year, created_at)
                VALUES (extract(year FROM relation.registered_on), relation.registered_on) RETURNING id INTO sequence_number;
            UPDATE sample_people SET osca_id_number = to_char(relation.registered_on, 'YY') || '-' ||
                CASE WHEN sequence_number < 10000 THEN lpad(sequence_number::text, 4, '0') ELSE sequence_number::text END WHERE n = relation.n;
            EXIT WHEN NOT EXISTS (SELECT FROM senior_citizens WHERE osca_id_number =
                (SELECT osca_id_number FROM sample_people WHERE n = relation.n));
        END LOOP;
    END LOOP;

    INSERT INTO senior_citizens (barangay_id, first_name, middle_name, last_name, birth_date,
        sex, address, osca_id_number, status, verified_at, verified_by, id_generated_at,
        is_bedridden, is_indigent, classification_verified_at, classification_verified_by,
        tss_verified_at, tss_verified_by, tss_request, created_at, updated_at)
    SELECT p.barangay_id, names.first_name, names.middle_name, names.last_name, p.birth_date,
        CASE WHEN p.n % 2 = 0 THEN 'MALE' ELSE 'FEMALE' END, 'SYNTHETIC ADDRESS - NOT A RESIDENCE',
        p.osca_id_number, 'VERIFIED', p.registered_on::timestamp + interval '8 hours', actor,
        p.registered_on::timestamp + interval '9 hours',
        p.benefit_code = 'october-assistance', p.benefit_code = 'october-assistance',
        CASE WHEN p.benefit_code = 'october-assistance' THEN p.registered_on::timestamp + interval '8 hours' ELSE NULL END,
        CASE WHEN p.benefit_code = 'october-assistance' THEN actor ELSE NULL END,
        CASE WHEN p.benefit_code = 'tss' THEN p.registered_on::timestamp + interval '8 hours' ELSE NULL END,
        CASE WHEN p.benefit_code = 'tss' THEN actor ELSE NULL END,
        CASE WHEN p.benefit_code = 'tss' THEN 'SYNTHETIC - sample medical assistance request' ELSE NULL END,
        p.registered_on::timestamp + interval '7 hours',
        p.registered_on::timestamp + interval '9 hours'
    FROM sample_people p
    JOIN sample_filipino_names names ON names.n = p.n ORDER BY p.n;

    ALTER TABLE sample_people ADD COLUMN senior_id bigint;
    UPDATE sample_people p SET senior_id = s.id FROM senior_citizens s WHERE s.osca_id_number = p.osca_id_number;

    INSERT INTO applications (senior_citizen_id, program_id, application_number, applied_on,
        status, remarks, reviewed_by, reviewed_at, created_at, updated_at)
    SELECT senior_id, program_id, 'SYN-APP-' || lpad(n::text, 5, '0'), '2026-10-21', 'APPROVED',
        'SYNTHETIC - no real application', actor, payout_at, payout_at, payout_at FROM sample_people;

    INSERT INTO beneficiaries (program_id, senior_citizen_id, application_id, enrolled_on, status,
        assigned_by, assigned_at, created_at, updated_at)
    SELECT p.program_id, p.senior_id, a.id, '2026-10-21', 'ACTIVE', actor, payout_at, payout_at, payout_at
    FROM sample_people p JOIN applications a ON a.senior_citizen_id = p.senior_id AND a.program_id = p.program_id;

    INSERT INTO benefit_entitlements (program_id, senior_citizen_id, period_key, milestone_age, eligibility_on, amount,
        status, benefit_schedule_id, rule_snapshot, reviewed_by, reviewed_at, claimed_at, review_note, created_at, updated_at)
    SELECT program_id, senior_id, period_key, milestone_age, '2026-10-21', amount, 'CLAIMED', schedule_id, rule_snapshot::json, actor,
        payout_at, payout_at, 'SYNTHETIC - no real claim', payout_at, payout_at FROM sample_people;

    INSERT INTO benefit_payments (benefit_entitlement_id, benefit_schedule_id, reference_number, amount,
        claimant_type, groceries_received, recorded_by, paid_at, created_at, updated_at)
    SELECT e.id, p.schedule_id, 'SYN-PAY-' || lpad(p.n::text, 5, '0'), p.amount, 'SELF',
        p.benefit_code = 'provincial-centenarian', actor, payout_at, payout_at, payout_at
    FROM sample_people p JOIN benefit_entitlements e
        ON e.senior_citizen_id = p.senior_id AND e.program_id = p.program_id AND e.period_key = p.period_key;

    INSERT INTO benefit_schedule_recipients (benefit_schedule_id, benefit_entitlement_id, outcome, created_at, updated_at)
    SELECT p.schedule_id, e.id, 'CLAIMED', payout_at, payout_at
    FROM sample_people p JOIN benefit_entitlements e
        ON e.senior_citizen_id = p.senior_id AND e.program_id = p.program_id AND e.period_key = p.period_key;

    IF (SELECT count(*) FROM senior_citizens WHERE id IN (SELECT senior_id FROM sample_people)) <> 10000
        OR (SELECT count(DISTINCT id) FROM senior_citizens WHERE id IN (SELECT senior_id FROM sample_people)) <> 10000
        OR (SELECT count(DISTINCT osca_id_number) FROM senior_citizens WHERE id IN (SELECT senior_id FROM sample_people)) <> 10000 THEN
        RAISE EXCEPTION 'Sample identity count failed';
    END IF;
    IF EXISTS (
        SELECT FROM senior_citizens WHERE id IN (SELECT senior_id FROM sample_people)
        GROUP BY last_name HAVING count(*) > 1
            AND (count(DISTINCT first_name) <> count(*) OR count(DISTINCT barangay_id) < 2)
    ) THEN
        RAISE EXCEPTION 'Shared surnames require different first names and barangays';
    END IF;
    IF EXISTS (
        SELECT FROM unnest(codes) WITH ORDINALITY c(code, position)
        LEFT JOIN barangays b ON b.code = c.code
        LEFT JOIN senior_citizens s ON s.barangay_id = b.id AND s.id IN (SELECT senior_id FROM sample_people)
        GROUP BY c.position HAVING count(s.id) <> CASE WHEN c.position <= 16 THEN 417 ELSE 416 END
    ) OR (SELECT count(DISTINCT barangay_id) FROM senior_citizens WHERE id IN (SELECT senior_id FROM sample_people)) <> 24 THEN
        RAISE EXCEPTION 'Barangay allocation failed';
    END IF;
    IF EXISTS (
        SELECT FROM generate_series(2000, 2026) y LEFT JOIN senior_citizens s
        ON extract(year FROM s.created_at) = y AND s.id IN (SELECT senior_id FROM sample_people)
        GROUP BY y HAVING count(s.id) <> CASE WHEN y < 2010 THEN 371 ELSE 370 END
    ) OR (SELECT count(DISTINCT extract(year FROM created_at)) FROM senior_citizens WHERE id IN (SELECT senior_id FROM sample_people)) <> 27 THEN
        RAISE EXCEPTION 'Registration year allocation failed';
    END IF;
    IF EXISTS (SELECT FROM senior_citizens s JOIN sample_people p ON p.senior_id = s.id
        WHERE s.created_at::date < DATE '2000-01-01' OR s.created_at::date > DATE '2026-10-21'
        OR s.birth_date + interval '60 years' > s.created_at OR s.verified_at < s.created_at
        OR s.id_generated_at < s.verified_at OR s.id_generated_at > payout_at
        OR s.osca_id_number !~ '^[0-9]{2}-[0-9]{4,}$'
        OR split_part(s.osca_id_number, '-', 1) <> to_char(s.id_generated_at, 'YY')
        OR NOT EXISTS (SELECT FROM osca_id_numbers o WHERE o.id = split_part(s.osca_id_number, '-', 2)::bigint
            AND o.issued_year = extract(year FROM s.id_generated_at))) THEN
        RAISE EXCEPTION 'Age, chronology or OSCA issuance failed';
    END IF;
    IF (SELECT count(DISTINCT program_id) FROM sample_people) <> 9
        OR (SELECT count(*) FROM applications WHERE application_number LIKE 'SYN-APP-%') <> 10000
        OR (SELECT count(*) FROM beneficiaries WHERE senior_citizen_id IN (SELECT senior_id FROM sample_people)) <> 10000
        OR (SELECT count(*) FROM benefit_entitlements WHERE senior_citizen_id IN (SELECT senior_id FROM sample_people)) <> 10000
        OR (SELECT count(*) FROM benefit_payments WHERE reference_number LIKE 'SYN-PAY-%') <> 10000
        OR (SELECT count(*) FROM benefit_schedule_recipients WHERE benefit_schedule_id IN (SELECT DISTINCT schedule_id FROM sample_people)) <> 10000 THEN
        RAISE EXCEPTION 'Related dataset counts failed';
    END IF;
    IF (SELECT count(DISTINCT p.senior_id) FROM sample_people p
        JOIN applications a ON a.senior_citizen_id = p.senior_id AND a.program_id = p.program_id
        JOIN beneficiaries b ON b.application_id = a.id AND b.senior_citizen_id = p.senior_id AND b.program_id = p.program_id
        JOIN benefit_entitlements e ON e.senior_citizen_id = p.senior_id AND e.program_id = p.program_id AND e.benefit_schedule_id = p.schedule_id
        JOIN benefit_payments pay ON pay.benefit_entitlement_id = e.id AND pay.benefit_schedule_id = p.schedule_id
        JOIN benefit_schedule_recipients r ON r.benefit_entitlement_id = e.id AND r.benefit_schedule_id = p.schedule_id
        WHERE pay.amount = e.amount AND pay.paid_at >= p.registered_on AND r.outcome = 'CLAIMED') <> 10000 THEN
        RAISE EXCEPTION 'Related dataset relationships failed';
    END IF;
    FOR relation IN SELECT oid, oid::regclass AS name FROM pg_class
        WHERE relnamespace = 'public'::regnamespace AND relkind = 'r' ORDER BY oid
    LOOP
        EXECUTE format('SELECT EXISTS (SELECT FROM sample_original_rows o LEFT JOIN %s t ON t.ctid = o.row_tid
            WHERE o.relation_id = %s AND (t.ctid IS NULL OR md5(to_jsonb(t)::text) <> o.fingerprint))', relation.name, relation.oid) INTO invalid;
        IF invalid THEN
            RAISE EXCEPTION 'Existing records changed; rolling back';
        END IF;
    END LOOP;
END $$;
