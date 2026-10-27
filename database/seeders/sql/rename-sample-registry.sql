SET LOCAL lock_timeout = '10s';
SET LOCAL statement_timeout = '180s';
SET LOCAL synchronous_commit = on;

DO $$
DECLARE
    relation record;
    invalid boolean;
    senior_count bigint;
    surname_only boolean := coalesce(nullif(current_setting('elingap.surname_only', true), ''), 'false')::boolean;
BEGIN
    PERFORM pg_advisory_xact_lock(194701, 1);
    FOR relation IN SELECT oid, oid::regclass AS name FROM pg_class
        WHERE relnamespace = 'public'::regnamespace AND relkind = 'r' ORDER BY oid
    LOOP
        EXECUTE format('LOCK TABLE %s IN SHARE ROW EXCLUSIVE MODE', relation.name);
    END LOOP;

    CREATE TEMP TABLE sample_name_targets ON COMMIT DROP AS
    SELECT s.id, n.n FROM senior_citizens s
    JOIN applications a ON a.senior_citizen_id = s.id
    JOIN sample_filipino_names n ON a.application_number = 'SYN-APP-' || lpad(n.n::text, 5, '0')
    JOIN beneficiaries b ON b.senior_citizen_id = s.id
    JOIN programs p ON p.id = b.program_id
    WHERE p.benefit_code = 'synthetic-registry-2000-2026'
        AND s.address = 'SYNTHETIC ADDRESS - NOT A RESIDENCE';
    ALTER TABLE sample_name_targets ADD PRIMARY KEY (id);
    IF (SELECT count(*) FROM sample_name_targets) <> 10000 THEN
        RAISE EXCEPTION 'Exactly 10,000 linked synthetic profiles are required';
    END IF;

    SELECT count(*) INTO senior_count FROM senior_citizens;
    CREATE TEMP TABLE sample_name_original_seniors ON COMMIT DROP AS
    SELECT s.id, md5(CASE WHEN t.id IS NOT NULL THEN to_jsonb(s) - CASE WHEN surname_only
        THEN ARRAY['last_name'] ELSE ARRAY['first_name','middle_name','last_name'] END
        ELSE to_jsonb(s) END::text) AS fingerprint
    FROM senior_citizens s LEFT JOIN sample_name_targets t ON t.id = s.id;

    CREATE TEMP TABLE sample_name_original_rows (relation_id oid, row_tid tid, fingerprint text) ON COMMIT DROP;
    FOR relation IN SELECT oid, oid::regclass AS name FROM pg_class
        WHERE relnamespace = 'public'::regnamespace AND relkind = 'r' AND oid <> 'senior_citizens'::regclass ORDER BY oid
    LOOP
        EXECUTE format('INSERT INTO sample_name_original_rows SELECT %s, ctid, md5(to_jsonb(t)::text) FROM %s t', relation.oid, relation.name);
    END LOOP;

    UPDATE senior_citizens s SET first_name = CASE WHEN surname_only THEN s.first_name ELSE n.first_name END,
        middle_name = CASE WHEN surname_only THEN s.middle_name ELSE n.middle_name END, last_name = n.last_name
    FROM sample_filipino_names n, sample_name_targets t
    WHERE s.id = t.id AND t.n = n.n
        AND ROW(s.first_name, s.middle_name, s.last_name) IS DISTINCT FROM ROW(n.first_name, n.middle_name, n.last_name);

    IF (SELECT count(*) FROM senior_citizens s JOIN sample_name_targets t ON t.id = s.id
        JOIN sample_filipino_names n ON n.n = t.n
        WHERE (surname_only OR (s.first_name = n.first_name AND s.middle_name = n.middle_name)) AND s.last_name = n.last_name) <> 10000
        OR (SELECT count(DISTINCT (s.first_name, s.middle_name, s.last_name)) FROM senior_citizens s
            JOIN sample_name_targets t ON t.id = s.id) <> 10000 THEN
        RAISE EXCEPTION 'Sample full-name acceptance check failed';
    END IF;

    IF EXISTS (
        SELECT FROM senior_citizens s JOIN sample_name_targets t ON t.id = s.id
        GROUP BY s.last_name HAVING count(*) > 1
            AND (count(DISTINCT s.first_name) <> count(*) OR count(DISTINCT s.barangay_id) < 2)
    ) THEN
        RAISE EXCEPTION 'Shared surnames require different first names and barangays';
    END IF;

    IF (SELECT count(*) FROM senior_citizens) <> senior_count OR EXISTS (
        SELECT FROM sample_name_original_seniors o LEFT JOIN senior_citizens s ON s.id = o.id
        LEFT JOIN sample_name_targets t ON t.id = o.id
        WHERE s.id IS NULL OR o.fingerprint <> md5(CASE WHEN t.id IS NOT NULL
            THEN to_jsonb(s) - CASE WHEN surname_only THEN ARRAY['last_name'] ELSE ARRAY['first_name','middle_name','last_name'] END
            ELSE to_jsonb(s) END::text)
    ) THEN
        RAISE EXCEPTION 'Changes outside sample name fields detected';
    END IF;
    FOR relation IN SELECT oid, oid::regclass AS name FROM pg_class
        WHERE relnamespace = 'public'::regnamespace AND relkind = 'r' AND oid <> 'senior_citizens'::regclass ORDER BY oid
    LOOP
        EXECUTE format('SELECT EXISTS (SELECT FROM sample_name_original_rows o LEFT JOIN %s t ON t.ctid = o.row_tid
            WHERE o.relation_id = %s AND (t.ctid IS NULL OR md5(to_jsonb(t)::text) <> o.fingerprint))
            OR (SELECT count(*) FROM %s) <> (SELECT count(*) FROM sample_name_original_rows WHERE relation_id = %s)',
            relation.name, relation.oid, relation.name, relation.oid) INTO invalid;
        IF invalid THEN
            RAISE EXCEPTION 'Changes to other datasets detected';
        END IF;
    END LOOP;
END $$;
