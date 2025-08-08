To generate the the test data:

```sql
-- Drop the table if it exists
DROP TABLE IF EXISTS items;

-- Create the items table
CREATE TABLE items (
    id SERIAL PRIMARY KEY,
    name TEXT,
    country TEXT,
    month TEXT,
    price NUMERIC(10, 2)
);

-- Insert sample data with 0–5 duplicates per (name, country, month)
DO $$
DECLARE
    names TEXT[] := ARRAY['itemA', 'itemB', 'itemC'];
    countries TEXT[] := ARRAY['CN', 'JP', 'KR'];
    months TEXT[] := ARRAY['202501', '202502', '202503', '202504', '202505'];
    n TEXT;
    c TEXT;
    m TEXT;
    i INT;
    dup_count INT;
BEGIN
    FOREACH n IN ARRAY names LOOP
        FOREACH c IN ARRAY countries LOOP
            FOREACH m IN ARRAY months LOOP
                -- Random number of duplicates between 0 and 5
                dup_count := FLOOR(RANDOM() * 6); -- 0 to 5

                FOR i IN 1..dup_count LOOP
                    INSERT INTO items (name, country, month, price)
                    VALUES (
                        n,
                        c,
                        m,
                        ROUND((10 + RANDOM() * 990)::numeric, 2)
                    );
                END LOOP;
            END LOOP;
        END LOOP;
    END LOOP;
END $$;
```

Export to JSON:

```sql
\copy (select json_agg(row_to_json(items)) from (select name, country, month, count(*), sum(price), grouping(name, country, month) from items group by cube(name, country, month)) items) to 'cube.json';

\copy (select json_agg(row_to_json(items)) from (select name, country, month, count(*), sum(price), grouping(name, country, month) from items group by rollup(name, country, month)) items) to 'rollup.json';

\copy (select json_agg(row_to_json(items)) from (select name, country, month, count(*), sum(price) from items group by name, country, month) items) to 'nogrouping.json';
```
