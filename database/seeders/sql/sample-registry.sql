SET LOCAL lock_timeout = '10s';
SET LOCAL statement_timeout = '180s';
SET LOCAL synchronous_commit = on;
SET LOCAL TIME ZONE 'Asia/Manila';

DO $$
DECLARE
    codes text[] := ARRAY['BAG','BAL','BUE','BUL','CAM','CAT','CAY','CYS','GUY','LAL','MAS','MHP','MAN','PAR','POB','PBU','SGA','SJP','SVI','SCL','SCR','SIL','TBA','TUM'];
    actor bigint;
    program bigint;
    schedule bigint;
    sequence_number bigint;
    relation record;
    invalid boolean;
    rules json := '{"cadence":"ON_DEMAND","synthetic":true}';
    payout_at timestamptz := '2026-10-21 12:00:00+08';
BEGIN
    PERFORM pg_advisory_xact_lock(194701, 1);
    -- Lock in a fixed order so concurrent writers cannot invalidate acceptance checks.
    FOR relation IN SELECT oid, oid::regclass AS name FROM pg_class
        WHERE relnamespace = 'public'::regnamespace AND relkind = 'r' ORDER BY oid
    LOOP
        EXECUTE format('LOCK TABLE %s IN SHARE ROW EXCLUSIVE MODE', relation.name);
    END LOOP;

    IF EXISTS (SELECT FROM programs WHERE benefit_code = 'synthetic-registry-2000-2026')
        OR EXISTS (SELECT FROM senior_citizens WHERE address = 'SYNTHETIC ADDRESS - NOT A RESIDENCE') THEN
        RAISE EXCEPTION 'Sample dataset or synthetic profiles already exists';
    END IF;
    IF (SELECT count(*) FROM barangays WHERE code = ANY(codes) AND is_active) <> 24 THEN
        RAISE EXCEPTION 'All 24 expected active barangays are required';
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
    )
    SELECT n, registered_on, b.id AS barangay_id,
        (registered_on - make_interval(years => 60 + n % 16))::date AS birth_date
    FROM registrations JOIN barangays b ON b.code = codes[1 + (n - 1) % 24];
    ALTER TABLE sample_people ADD PRIMARY KEY (n);

    INSERT INTO programs (name, agency, budget, starts_on, ends_on, status, description, created_by,
        benefit_code, government_level, benefit_rules, default_amount, provisional, created_at, updated_at)
    VALUES ('SYNTHETIC - Sample Senior Assistance', 'SYNTHETIC - OSCA Santa Maria', 5000000,
        '2026-10-21', '2026-10-21', 'ACTIVE', 'Mock data only; no real disbursements.', actor,
        'synthetic-registry-2000-2026', 'LGU', rules, 500, false, payout_at, payout_at) RETURNING id INTO program;

    INSERT INTO benefit_schedules (program_id, reference_number, period_key, eligibility_on, scheduled_at,
        location, batch_name, barangay_ids, amount, available_funds, rule_snapshot, status,
        created_by, approved_by, approved_at, closed_at, created_at, updated_at)
    VALUES (program, 'SYN-SCHEDULE-2026', '2026-SAMPLE', '2026-10-21', payout_at,
        'SYNTHETIC - Santa Maria', 'SYNTHETIC - 10,000 samples',
        (SELECT json_agg(id ORDER BY id) FROM barangays WHERE code = ANY(codes)),
        500, 5000000, rules, 'CLOSED', actor, actor, payout_at, payout_at, payout_at, payout_at)
    RETURNING id INTO schedule;

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
        sex, address, osca_id_number, status, verified_at, verified_by, id_generated_at, created_at, updated_at)
    SELECT barangay_id, names.first_name, names.middle_name, names.last_name, birth_date,
        CASE WHEN p.n % 2 = 0 THEN 'MALE' ELSE 'FEMALE' END, 'SYNTHETIC ADDRESS - NOT A RESIDENCE',
        osca_id_number, 'VERIFIED', registered_on::timestamp + interval '8 hours', actor,
        registered_on::timestamp + interval '9 hours', registered_on::timestamp + interval '7 hours',
        registered_on::timestamp + interval '9 hours' FROM sample_people p
        JOIN sample_filipino_names names ON names.n = p.n ORDER BY p.n;

    ALTER TABLE sample_people ADD COLUMN senior_id bigint;
    UPDATE sample_people p SET senior_id = s.id FROM senior_citizens s WHERE s.osca_id_number = p.osca_id_number;

    INSERT INTO applications (senior_citizen_id, program_id, application_number, applied_on,
        status, remarks, reviewed_by, reviewed_at, created_at, updated_at)
    SELECT senior_id, program, 'SYN-APP-' || lpad(n::text, 5, '0'), '2026-10-21', 'APPROVED',
        'SYNTHETIC - no real application', actor, payout_at, payout_at, payout_at FROM sample_people;

    INSERT INTO beneficiaries (program_id, senior_citizen_id, application_id, enrolled_on, status,
        assigned_by, assigned_at, created_at, updated_at)
    SELECT program, p.senior_id, a.id, '2026-10-21', 'ACTIVE', actor, payout_at, payout_at, payout_at
    FROM sample_people p JOIN applications a ON a.senior_citizen_id = p.senior_id AND a.program_id = program;

    INSERT INTO benefit_entitlements (program_id, senior_citizen_id, period_key, eligibility_on, amount,
        status, benefit_schedule_id, rule_snapshot, reviewed_by, reviewed_at, claimed_at, review_note, created_at, updated_at)
    SELECT program, senior_id, '2026-SAMPLE', '2026-10-21', 500, 'CLAIMED', schedule, rules, actor,
        payout_at, payout_at, 'SYNTHETIC - no real claim', payout_at, payout_at FROM sample_people;

    INSERT INTO benefit_payments (benefit_entitlement_id, benefit_schedule_id, reference_number, amount,
        claimant_type, recorded_by, paid_at, created_at, updated_at)
    SELECT e.id, schedule, 'SYN-PAY-' || lpad(p.n::text, 5, '0'), 500, 'SELF', actor, payout_at, payout_at, payout_at
    FROM sample_people p JOIN benefit_entitlements e ON e.senior_citizen_id = p.senior_id AND e.program_id = program;

    INSERT INTO benefit_schedule_recipients (benefit_schedule_id, benefit_entitlement_id, outcome, created_at, updated_at)
    SELECT schedule, id, 'CLAIMED', payout_at, payout_at FROM benefit_entitlements WHERE program_id = program;

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
    IF (SELECT count(*) FROM applications WHERE program_id = program) <> 10000
        OR (SELECT count(*) FROM beneficiaries WHERE program_id = program) <> 10000
        OR (SELECT count(*) FROM benefit_entitlements WHERE program_id = program) <> 10000
        OR (SELECT count(*) FROM benefit_payments WHERE benefit_schedule_id = schedule) <> 10000
        OR (SELECT count(*) FROM benefit_schedule_recipients WHERE benefit_schedule_id = schedule) <> 10000 THEN
        RAISE EXCEPTION 'Related dataset counts failed';
    END IF;
    IF (SELECT count(DISTINCT p.senior_id) FROM sample_people p
        JOIN applications a ON a.senior_citizen_id = p.senior_id AND a.program_id = program
        JOIN beneficiaries b ON b.application_id = a.id AND b.senior_citizen_id = p.senior_id AND b.program_id = program
        JOIN benefit_entitlements e ON e.senior_citizen_id = p.senior_id AND e.program_id = program AND e.benefit_schedule_id = schedule
        JOIN benefit_payments pay ON pay.benefit_entitlement_id = e.id AND pay.benefit_schedule_id = schedule
        JOIN benefit_schedule_recipients r ON r.benefit_entitlement_id = e.id AND r.benefit_schedule_id = schedule
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
