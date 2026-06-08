-- ============================================================
--  DOTA 2 ANALYTICS  |  Hero Win Rate & Pick Rate
-- ============================================================

-- ────────────────────────────────────────────────────────────
--  KWERENDA 1: WIN RATE per bohater
--  Win Rate = Mecze wygrane tym bohaterem / Wszystkie mecze
--             rozegrane tym bohaterem * 100%
--
--  Logika:
--   • Hero_Played (hp) łączy bohatera z konkretnym slotem w Team
--   • Team (t) łączy hp z meczem przez HP1..HP5
--   • Match_Game (m) zawiera WINNER_ID (ID drużyny która wygrała)
--   • Wygrana = t.ID = m.WINNER_ID
-- ────────────────────────────────────────────────────────────

SELECT
    h.ID                                        AS HERO_ID,
    h.NAME                                      AS HERO_NAME,
    h.PRIMARY_ATTRIBUTE                         AS ATRYBUT,
    COUNT(hp.ID)                                AS MECZE_RAZEM,
    SUM(CASE WHEN t.ID = m.WINNER_ID THEN 1
             ELSE 0 END)                        AS MECZE_WYGRANE,
    ROUND(
        SUM(CASE WHEN t.ID = m.WINNER_ID THEN 1
                 ELSE 0 END)
        / NULLIF(COUNT(hp.ID), 0) * 100
    , 2)                                        AS WIN_RATE_PCT
FROM SYS.Hero h
-- każdy wybór bohatera w meczu
JOIN SYS.Hero_Played hp
    ON hp.HERO_ID = h.ID
-- drużyna do której należał ten Hero_Played
JOIN SYS.Team t
    ON  t.HP1 = hp.ID OR t.HP2 = hp.ID
     OR t.HP3 = hp.ID OR t.HP4 = hp.ID
     OR t.HP5 = hp.ID
-- mecz w którym wystąpiła ta drużyna
JOIN SYS.Match_Game m
    ON m.TEAM1_ID = t.ID OR m.TEAM2_ID = t.ID
GROUP BY
    h.ID,
    h.NAME,
    h.PRIMARY_ATTRIBUTE
ORDER BY
    WIN_RATE_PCT DESC NULLS LAST,
    MECZE_RAZEM  DESC;


-- ────────────────────────────────────────────────────────────
--  KWERENDA 2: PICK RATE per bohater
--  Pick Rate = Liczba wyborów bohatera /
--              Łączna liczba wszystkich wyborów * 100%
-- ────────────────────────────────────────────────────────────

SELECT
    h.ID                                        AS HERO_ID,
    h.NAME                                      AS HERO_NAME,
    h.PRIMARY_ATTRIBUTE                         AS ATRYBUT,
    COUNT(hp.ID)                                AS ILOSC_WYBOROW,
    SUM(COUNT(hp.ID)) OVER ()                   AS WYBORY_LACZNIE,
    ROUND(
        COUNT(hp.ID)
        / SUM(COUNT(hp.ID)) OVER () * 100
    , 2)                                        AS PICK_RATE_PCT
FROM SYS.Hero h
JOIN SYS.Hero_Played hp
    ON hp.HERO_ID = h.ID
GROUP BY
    h.ID,
    h.NAME,
    h.PRIMARY_ATTRIBUTE
ORDER BY
    PICK_RATE_PCT DESC NULLS LAST;


-- ────────────────────────────────────────────────────────────
--  KWERENDA 3: WIN RATE + PICK RATE w jednym zestawieniu
--  (połączenie obu kwerend przez CTE)
-- ────────────────────────────────────────────────────────────

WITH picks AS (
    -- ile razy każdy bohater był wybrany
    SELECT
        hp.HERO_ID,
        COUNT(hp.ID)            AS ILOSC_WYBOROW
    FROM SYS.Hero_Played hp
    GROUP BY hp.HERO_ID
),
total_picks AS (
    -- łączna liczba wszystkich wyborów
    SELECT SUM(ILOSC_WYBOROW) AS SUMA FROM picks
),
wins AS (
    -- ile razy każdy bohater wygrał
    SELECT
        hp.HERO_ID,
        COUNT(hp.ID)            AS MECZE_RAZEM,
        SUM(CASE WHEN t.ID = m.WINNER_ID THEN 1 ELSE 0 END) AS WYGRANE
    FROM SYS.Hero_Played hp
    JOIN SYS.Team t
        ON  t.HP1 = hp.ID OR t.HP2 = hp.ID
         OR t.HP3 = hp.ID OR t.HP4 = hp.ID
         OR t.HP5 = hp.ID
    JOIN SYS.Match_Game m
        ON m.TEAM1_ID = t.ID OR m.TEAM2_ID = t.ID
    GROUP BY hp.HERO_ID
)
SELECT
    h.ID                                        AS HERO_ID,
    h.NAME                                      AS HERO_NAME,
    h.PRIMARY_ATTRIBUTE                         AS ATRYBUT,
    COALESCE(p.ILOSC_WYBOROW, 0)               AS WYBORY,
    COALESCE(w.MECZE_RAZEM,   0)               AS MECZE_Z_MATCH,
    COALESCE(w.WYGRANE,       0)               AS WYGRANE,
    -- Win Rate
    ROUND(
        COALESCE(w.WYGRANE, 0)
        / NULLIF(COALESCE(w.MECZE_RAZEM, 0), 0) * 100
    , 2)                                        AS WIN_RATE_PCT,
    -- Pick Rate
    ROUND(
        COALESCE(p.ILOSC_WYBOROW, 0)
        / NULLIF(tp.SUMA, 0) * 100
    , 2)                                        AS PICK_RATE_PCT
FROM SYS.Hero h
LEFT JOIN picks        p  ON p.HERO_ID = h.ID
LEFT JOIN wins         w  ON w.HERO_ID = h.ID
CROSS JOIN total_picks tp
ORDER BY
    PICK_RATE_PCT DESC NULLS LAST,
    WIN_RATE_PCT  DESC NULLS LAST;




















--Win Rate dla Invokera (dla weryfikacji poprawności kwerendy)


SELECT
    h.NAME                                      AS HERO_NAME,
    ROUND(
        SUM(CASE WHEN t.ID = m.WINNER_ID THEN 1
                 ELSE 0 END)
        / NULLIF(COUNT(hp.ID), 0) * 100
    , 2)                                        AS WIN_RATE_PCT
FROM SYS.Hero h
JOIN SYS.Hero_Played hp
    ON hp.HERO_ID = h.ID
JOIN SYS.Team t
    ON  t.HP1 = hp.ID OR t.HP2 = hp.ID
     OR t.HP3 = hp.ID OR t.HP4 = hp.ID
     OR t.HP5 = hp.ID
JOIN SYS.Match_Game m
    ON m.TEAM1_ID = t.ID OR m.TEAM2_ID = t.ID
GROUP BY
    h.NAME
HAVING h.NAME = 'Invoker'