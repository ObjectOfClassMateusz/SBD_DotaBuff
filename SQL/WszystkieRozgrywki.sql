-- Wszyskie rozgrywki        
SELECT
            hp.ID, hp.POSITION, hp.KILLS, hp.DEATHS, hp.ASSISTS, hp.NETTO, hp.KDA,
            h.NAME AS HERO_NAME, h.PRIMARY_ATTRIBUTE,
            p.NICKNAME, p.RANK, p.STEAM_ID,
            i1.NAME AS ITEM1, i2.NAME AS ITEM2, i3.NAME AS ITEM3,
            i4.NAME AS ITEM4, i5.NAME AS ITEM5, i6.NAME AS ITEM6
         FROM SYS.Hero_Played hp
         JOIN SYS.Hero   h  ON h.ID  = hp.HERO_ID
         JOIN SYS.Player p  ON p.STEAM_ID = hp.STEAM_ID
         LEFT JOIN SYS.Item i1 ON i1.ID = hp.SLOT1
         LEFT JOIN SYS.Item i2 ON i2.ID = hp.SLOT2
         LEFT JOIN SYS.Item i3 ON i3.ID = hp.SLOT3
         LEFT JOIN SYS.Item i4 ON i4.ID = hp.SLOT4
         LEFT JOIN SYS.Item i5 ON i5.ID = hp.SLOT5
         LEFT JOIN SYS.Item i6 ON i6.ID = hp.SLOT6;


--Wystąpienia przedmiotów w rozgrywkach
SELECT 
    item_id,
    COUNT(*) AS wystapienia
FROM (
    SELECT i1.ID AS item_id
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i1 ON i1.ID = hp.SLOT1

    UNION ALL

    SELECT i2.ID
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i2 ON i2.ID = hp.SLOT2

    UNION ALL

    SELECT i3.ID
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i3 ON i3.ID = hp.SLOT3

    UNION ALL

    SELECT i4.ID
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i4 ON i4.ID = hp.SLOT4

    UNION ALL

    SELECT i5.ID
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i5 ON i5.ID = hp.SLOT5

    UNION ALL

    SELECT i6.ID
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i6 ON i6.ID = hp.SLOT6
) t
WHERE item_id IS NOT NULL
GROUP BY item_id
ORDER BY wystapienia DESC;