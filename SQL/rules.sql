GRANT SELECT ON SYS.Item TO C##dota_app;
GRANT INSERT ON SYS.Item TO C##dota_app;
GRANT DELETE ON SYS.Item TO C##dota_app;
GRANT UPDATE ON SYS.Item TO C##dota_app;

GRANT SELECT ON SYS.Hero TO C##dota_app;
GRANT INSERT ON SYS.Hero TO C##dota_app;
GRANT DELETE ON SYS.Hero TO C##dota_app;
GRANT UPDATE ON SYS.Hero TO C##dota_app;

GRANT SELECT ON SYS.Player TO C##dota_app;
GRANT INSERT ON SYS.Player TO C##dota_app;
GRANT DELETE ON SYS.Player TO C##dota_app;
GRANT UPDATE ON SYS.Player TO C##dota_app;

GRANT SELECT ON SYS.match_game TO C##dota_app;
GRANT INSERT ON SYS.match_game TO C##dota_app;
GRANT DELETE ON SYS.match_game TO C##dota_app;
GRANT UPDATE ON SYS.match_game TO C##dota_app;

GRANT SELECT ON SYS.Hero_Played TO C##dota_app;
GRANT INSERT ON SYS.Hero_Played TO C##dota_app;
GRANT DELETE ON SYS.Hero_Played TO C##dota_app;
GRANT UPDATE ON SYS.Hero_Played TO C##dota_app;

GRANT SELECT ON SYS.team TO C##dota_app;
GRANT INSERT ON SYS.team TO C##dota_app;
GRANT DELETE ON SYS.team TO C##dota_app;
GRANT UPDATE ON SYS.team TO C##dota_app;




-- ============================================================
--  UPRAWNIENIA DLA C##dota_app
--  Uruchom jako SYS (SYSDBA) w SQL Developer
-- ============================================================

-- ── 1. DML na wszystkich tabelach ─────────────────────────
GRANT SELECT, INSERT, UPDATE, DELETE ON SYS.Item        TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON SYS.Hero        TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON SYS.Player      TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON SYS.Hero_Played TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON SYS.Team        TO C##dota_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON SYS.Match_Game  TO C##dota_app;

-- ── 2. Sekwencje (SELECT = NEXTVAL + CURRVAL) ─────────────
GRANT SELECT ON SYS.SEQ_ITEM_ID       TO C##dota_app;
GRANT SELECT ON SYS.SEQ_HERO_ID       TO C##dota_app;
GRANT SELECT ON SYS.SEQ_HEROPLAYED_ID TO C##dota_app;
GRANT SELECT ON SYS.SEQ_TEAM_ID       TO C##dota_app;
GRANT SELECT ON SYS.SEQ_MATCH_ID      TO C##dota_app;

-- ── 3. Weryfikacja — sprawdź czy granty są widoczne ───────
SELECT PRIVILEGE, TABLE_NAME
FROM   DBA_TAB_PRIVS
WHERE  GRANTEE = 'C##DOTA_APP'
ORDER  BY TABLE_NAME, PRIVILEGE;

