-- ============================================================
--  VERIFICATION
-- ============================================================
SELECT 'Items'   AS entity, COUNT(*) AS total FROM Item
UNION ALL
SELECT 'Heroes'  AS entity, COUNT(*) AS total FROM Hero
UNION ALL
SELECT 'Players' AS entity, COUNT(*) AS total FROM Player;

SELECT primary_attribute, COUNT(*) AS hero_count
FROM Hero GROUP BY primary_attribute ORDER BY hero_count DESC;

SELECT rank, COUNT(*) AS player_count
FROM Player GROUP BY rank
ORDER BY CASE rank
    WHEN 'Herald'   THEN 1 WHEN 'Guardian'  THEN 2
    WHEN 'Crusader' THEN 3 WHEN 'Archon'    THEN 4
    WHEN 'Legend'   THEN 5 WHEN 'Ancient'   THEN 6
    WHEN 'Divine'   THEN 7 WHEN 'Immortal'  THEN 8
END;

SELECT * FROM Hero;
SELECT * FROM Item;
SELECT * FROM Player;
SELECT * FROM Hero_Played;
SELECT * FROM match_game;
SELECT * FROM team;