-- Best KDA in all recorded matches
SELECT
    p.nickname,
    h.name        AS hero,
    hp.position,
    hp.kills,
    hp.deaths,
    hp.assists,
    ROUND(hp.kda, 2) AS kda,
    hp.netto
FROM Hero_Played hp
JOIN Player p ON p.steam_id  = hp.steam_id
JOIN Hero   h ON h.id        = hp.hero_id
ORDER BY hp.kda DESC
FETCH FIRST 10 ROWS ONLY;