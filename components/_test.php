<?php
require_once 'db.php';
require_once 'header.php';
?>

<?php
if ($db_error) {
    echo "<p style='color: red;'>Database connection error: $db_error</p>";
} else {
    echo "<p style='color: green;'>Database connection successful!</p>";
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sql = db_query($db_conn, "SELECT p.nickname,h.name AS hero, hp.position,
    hp.kills,
    hp.deaths,
    hp.assists,
    ROUND(hp.kda, 2) AS kda,
    hp.netto
FROM SYS.Hero_Played hp
JOIN SYS.Player p ON p.steam_id  = hp.steam_id
JOIN SYS.Hero   h ON h.id        = hp.hero_id
ORDER BY hp.kda DESC
FETCH FIRST 10 ROWS ONLY"); 
echo "<h2>Top 10 Hero Played by KDA</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Nickname</th><th>Hero</th><th>Position</th><th>KDA</th></tr>";

foreach ($sql as $row) {
    echo "<tr>";
    echo "<td>{$row['NICKNAME']}</td>";
    echo "<td>{$row['HERO']}</td>";
    echo "<td>{$row['POSITION']}</td>";

    echo "<td>{$row['KDA']}</td>";
    echo "</tr>";
}
echo "</table>";
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$sql2 = db_query($db_conn, "SELECT 
    item,
    COUNT(*) AS wystapienia
FROM (
    SELECT i1.Name AS item
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i1 ON i1.ID = hp.SLOT1
    UNION ALL
    SELECT i2.Name AS item
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i2 ON i2.ID = hp.SLOT2
    UNION ALL
    SELECT i3.Name AS item
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i3 ON i3.ID = hp.SLOT3
    UNION ALL
    SELECT i4.Name AS item
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i4 ON i4.ID = hp.SLOT4
    UNION ALL
    SELECT i5.Name AS item
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i5 ON i5.ID = hp.SLOT5
    UNION ALL
    SELECT i6.Name AS item
    FROM SYS.Hero_Played hp
    LEFT JOIN SYS.Item i6 ON i6.ID = hp.SLOT6
) t
WHERE item IS NOT NULL
GROUP BY item
ORDER BY wystapienia DESC
FETCH FIRST 10 ROWS ONLY");

echo "<h2>Top 10 Most Used Items</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";

foreach ($sql2 as $row) {
    echo "<tr>";
    echo "<td>{$row['ITEM']}</td>";
    echo "<td>{$row['WYSTAPIENIA']}</td>";
    echo "</tr>";
}
echo "</table>";
/////////////////////////////////////////////


$sql3 = db_query($db_conn, "SELECT
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
HAVING h.NAME = 'Invoker'");

echo "<h2>Hero Win Rates</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>
        <th>Hero</th>
        <th>WIN_RATE_PCT</th>
    </tr>";
    foreach ($sql3 as $row) 
        {
            echo "<tr>";
            $hero_name = strtolower(htmlspecialchars($row['HERO_NAME'] ?? 'Unknown'));
            $hero_name = str_replace(' ', '-', $hero_name);
            echo '<td><img style="margin-right:4px; vertical-align:middle; width:100px;"
                       src="https://pl.dotabuff.com/assets/heroes/' . $hero_name . '.jpg"
                       alt="hero_img_error"</td>';
            echo "<td>{$row['WIN_RATE_PCT']}%</td>";
            echo "</tr>";
    }
echo "</table>";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$sql4 = db_query($db_conn, "SELECT
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
    PICK_RATE_PCT DESC NULLS LAST");

echo "<h2>Hero Pick Rates</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>
        <th>Hero</th>
        <th>Pick Rate (%)</th>
    </tr>";
    foreach ($sql4 as $row) 
        {
            echo "<tr>";
            $hero_name = strtolower(htmlspecialchars($row['HERO_NAME'] ?? 'Unknown'));
            $hero_name = str_replace(' ', '-', $hero_name);
            echo '<td><img style="margin-right:4px; vertical-align:middle; width:100px;"
                       src="https://pl.dotabuff.com/assets/heroes/' . $hero_name . '.jpg"
                       alt="hero_img_error"</td>';
            echo "<td>{$row['PICK_RATE_PCT']}%</td>";
            echo "</tr>";
    }
echo "</table>";

require_once 'footer.php';
?>