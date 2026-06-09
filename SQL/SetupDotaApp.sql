CREATE USER C##dota_app IDENTIFIED BY qwerty123;
GRANT CONNECT TO C##dota_app;

-- ── 1. DML na wszystkich tabelach ─────────────────────────
GRANT SELECT, INSERT, UPDATE, DELETE ON Item        TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON Hero        TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON Player      TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON Hero_Played TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON Team        TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON Match_Game  TO C##dota_app;

GRANT EXECUTE ON GeneralInfo TO C##dota_app;
GRANT EXECUTE ON HeroStatistics TO C##dota_app;
GRANT EXECUTE ON PlayerQuery TO C##dota_app;
GRANT EXECUTE ON PlayerStatistics TO C##dota_app;
GRANT EXECUTE ON DatabaseAdministration TO C##dota_app;

GRANT EXECUTE ON HeroPlayedDTO TO C##dota_app;
GRANT EXECUTE ON TeamDTO TO C##dota_app;
GRANT EXECUTE ON ItemPopularity TO C##dota_app;
GRANT EXECUTE ON ItemPopularityList TO C##dota_app;
GRANT EXECUTE ON PatchChangeDTO TO C##dota_app;
GRANT EXECUTE ON PatchChangeList TO C##dota_app;
GRANT EXECUTE ON RankedHero TO C##dota_app;
GRANT EXECUTE ON RankedHeroList TO C##dota_app;
GRANT EXECUTE ON ChangedHero TO C##dota_app;
GRANT EXECUTE ON QueriedMatch TO C##dota_app;
GRANT EXECUTE ON MatchList TO C##dota_app;
GRANT EXECUTE ON HeroFrequency TO C##dota_app;
GRANT EXECUTE ON HeroFrequencyList TO C##dota_app;

-- ── 2. Sekwencje (SELECT = NEXTVAL + CURRVAL) ─────────────
GRANT SELECT ON SEQ_ITEM_ID       TO C##dota_app;
GRANT SELECT ON SEQ_HERO_ID       TO C##dota_app;
GRANT SELECT ON SEQ_HEROPLAYED_ID TO C##dota_app;
GRANT SELECT ON SEQ_TEAM_ID       TO C##dota_app;
GRANT SELECT ON SEQ_MATCH_ID      TO C##dota_app;

-- ── 3. Weryfikacja — sprawdź czy granty są widoczne ───────
SELECT PRIVILEGE, TABLE_NAME
FROM   DBA_TAB_PRIVS
WHERE  GRANTEE = 'C##DOTA_APP'
ORDER  BY TABLE_NAME, PRIVILEGE;

COMMIT;