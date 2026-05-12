-- ============================================================
--  DOTA 2 ANALYSIS APP  |  Relational Tables Extension
--  Requires existing tables: Item, Hero, Player
--  Creation order: HeroPlayed → Team → Match
-- ============================================================

-- ────────────────────────────────────────────────────────────
--  DROP (safe re-run — reverse FK order: Match → Team → HeroPlayed)
-- ────────────────────────────────────────────────────────────
BEGIN
    FOR t IN (SELECT table_name FROM user_tables
              WHERE table_name IN ('MATCH_GAME','TEAM','HERO_PLAYED'))
    LOOP
        EXECUTE IMMEDIATE 'DROP TABLE ' || t.table_name || ' CASCADE CONSTRAINTS';
    END LOOP;
END;
/

BEGIN
    FOR s IN (SELECT sequence_name FROM user_sequences
              WHERE sequence_name IN ('SEQ_HEROPLAYED_ID','SEQ_TEAM_ID','SEQ_MATCH_ID'))
    LOOP
        EXECUTE IMMEDIATE 'DROP SEQUENCE ' || s.sequence_name;
    END LOOP;
END;
/

-- ────────────────────────────────────────────────────────────
--  SEQUENCES
-- ────────────────────────────────────────────────────────────
CREATE SEQUENCE seq_heroplayed_id START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_team_id       START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_match_id      START WITH 1 INCREMENT BY 1 NOCACHE;


-- ============================================================
--  TABLE: HERO_PLAYED
--  One row = one player's performance in one match.
--  Slots 1-6 reference items purchased during the game.
--  Items are nullable — a player may not fill all 6 slots.
-- ============================================================
CREATE TABLE Hero_Played (
    id        NUMBER        DEFAULT seq_heroplayed_id.NEXTVAL
                            CONSTRAINT pk_heroplayed PRIMARY KEY,

    -- Who played
    steam_id  NUMBER(17)    CONSTRAINT nn_hp_steam NOT NULL,
                            CONSTRAINT fk_hp_player
                                FOREIGN KEY (steam_id) REFERENCES Player (steam_id),

    -- Which hero
    hero_id   NUMBER        CONSTRAINT nn_hp_hero NOT NULL,
                            CONSTRAINT fk_hp_hero
                                FOREIGN KEY (hero_id)  REFERENCES Hero (id),

    -- Position on the map (1 = carry ... 5 = hard support)
    position  NUMBER(1)     CONSTRAINT nn_hp_pos NOT NULL,
                            CONSTRAINT ck_hp_pos CHECK (position BETWEEN 1 AND 5),

    -- Item slots (NULL = empty slot)
    slot1     NUMBER        CONSTRAINT fk_hp_slot1 REFERENCES Item (id),
    slot2     NUMBER        CONSTRAINT fk_hp_slot2 REFERENCES Item (id),
    slot3     NUMBER        CONSTRAINT fk_hp_slot3 REFERENCES Item (id),
    slot4     NUMBER        CONSTRAINT fk_hp_slot4 REFERENCES Item (id),
    slot5     NUMBER        CONSTRAINT fk_hp_slot5 REFERENCES Item (id),
    slot6     NUMBER        CONSTRAINT fk_hp_slot6 REFERENCES Item (id),

    -- Performance stats
    kills     NUMBER(3)     DEFAULT 0 CONSTRAINT nn_hp_kills NOT NULL,
    deaths    NUMBER(3)     DEFAULT 0 CONSTRAINT nn_hp_deaths NOT NULL,
    assists   NUMBER(3)     DEFAULT 0 CONSTRAINT nn_hp_assists NOT NULL,

    -- Net worth in gold
    netto     NUMBER(7)     DEFAULT 0 CONSTRAINT nn_hp_netto NOT NULL,

    -- Derived KDA stored for fast querying
    -- Formula: (kills + assists) / GREATEST(deaths, 1)
    kda       NUMBER(6,2)   GENERATED ALWAYS AS (
                  (kills + assists) / GREATEST(deaths, 1)
              ) VIRTUAL
);

COMMENT ON TABLE  Hero_Played          IS 'Single player performance record within one match';
COMMENT ON COLUMN Hero_Played.position IS '1=Carry, 2=Mid, 3=Offlane, 4=Soft Support, 5=Hard Support';
COMMENT ON COLUMN Hero_Played.kda      IS 'Virtual: (Kills+Assists)/MAX(Deaths,1) — computed automatically';
COMMENT ON COLUMN Hero_Played.netto    IS 'Total net worth (gold) at end of game';
COMMENT ON COLUMN Hero_Played.slot1    IS 'Item in inventory slot 1 (NULL = empty)';


-- ============================================================
--  TABLE: TEAM
--  One row = one team side in one match.
--  HP1..HP5 are the five Hero_Played entries for this team.
-- ============================================================
CREATE TABLE Team (
    id    NUMBER       DEFAULT seq_team_id.NEXTVAL
                       CONSTRAINT pk_team PRIMARY KEY,

    side  VARCHAR2(7)  CONSTRAINT nn_team_side NOT NULL,
                       CONSTRAINT ck_team_side CHECK (side IN ('Radiant','Dire')),

    -- Five player slots — all required for a valid Dota 2 team
    hp1   NUMBER       CONSTRAINT nn_team_hp1 NOT NULL,
                       CONSTRAINT fk_team_hp1 FOREIGN KEY (hp1) REFERENCES Hero_Played (id),

    hp2   NUMBER       CONSTRAINT nn_team_hp2 NOT NULL,
                       CONSTRAINT fk_team_hp2 FOREIGN KEY (hp2) REFERENCES Hero_Played (id),

    hp3   NUMBER       CONSTRAINT nn_team_hp3 NOT NULL,
                       CONSTRAINT fk_team_hp3 FOREIGN KEY (hp3) REFERENCES Hero_Played (id),

    hp4   NUMBER       CONSTRAINT nn_team_hp4 NOT NULL,
                       CONSTRAINT fk_team_hp4 FOREIGN KEY (hp4) REFERENCES Hero_Played (id),

    hp5   NUMBER       CONSTRAINT nn_team_hp5 NOT NULL,
                       CONSTRAINT fk_team_hp5 FOREIGN KEY (hp5) REFERENCES Hero_Played (id),

    -- Each Hero_Played row belongs to exactly one team
    CONSTRAINT uq_team_hp1 UNIQUE (hp1),
    CONSTRAINT uq_team_hp2 UNIQUE (hp2),
    CONSTRAINT uq_team_hp3 UNIQUE (hp3),
    CONSTRAINT uq_team_hp4 UNIQUE (hp4),
    CONSTRAINT uq_team_hp5 UNIQUE (hp5)
);

COMMENT ON TABLE  Team      IS 'One team (5 players) participating on one side of a match';
COMMENT ON COLUMN Team.side IS 'Radiant or Dire — the two map sides in Dota 2';
COMMENT ON COLUMN Team.hp1  IS 'Hero_Played record for player in position 1';
COMMENT ON COLUMN Team.hp5  IS 'Hero_Played record for player in position 5';


-- ============================================================
--  TABLE: MATCH_GAME
--  (Named MATCH_GAME because MATCH is a reserved word in Oracle)
--  One row = one completed Dota 2 game.
-- ============================================================
CREATE TABLE Match_Game (
    id         NUMBER         DEFAULT seq_match_id.NEXTVAL
                              CONSTRAINT pk_match PRIMARY KEY,

    -- When the match was played
    match_time TIMESTAMP      CONSTRAINT nn_match_time NOT NULL,

    -- The two competing teams
    team1_id   NUMBER         CONSTRAINT nn_match_t1 NOT NULL,
                              CONSTRAINT fk_match_team1
                                  FOREIGN KEY (team1_id) REFERENCES Team (id),

    team2_id   NUMBER         CONSTRAINT nn_match_t2 NOT NULL,
                              CONSTRAINT fk_match_team2
                                  FOREIGN KEY (team2_id) REFERENCES Team (id),

    -- Winner — must be one of the two teams
    winner_id  NUMBER         CONSTRAINT nn_match_winner NOT NULL,
                              CONSTRAINT fk_match_winner
                                  FOREIGN KEY (winner_id) REFERENCES Team (id),

    -- Ranked or unranked game
    is_ranked  NUMBER(1)      DEFAULT 1 CONSTRAINT nn_match_ranked NOT NULL,
                              CONSTRAINT ck_match_ranked CHECK (is_ranked IN (0, 1)),

    -- Teams must be different
    CONSTRAINT ck_match_teams CHECK (team1_id <> team2_id),

    -- Winner must be one of the two participating teams
    CONSTRAINT ck_match_winner CHECK (
        winner_id = team1_id OR winner_id = team2_id
    )
);

COMMENT ON TABLE  Match_Game            IS 'A single completed Dota 2 game';
COMMENT ON COLUMN Match_Game.match_time IS 'Timestamp when the match started';
COMMENT ON COLUMN Match_Game.winner_id  IS 'FK to Team — must equal team1_id or team2_id';
COMMENT ON COLUMN Match_Game.is_ranked  IS '1 = Ranked match, 0 = Unranked / Normal game';


-- ============================================================
--  SAMPLE DATA  (2 complete matches)
-- ============================================================

-- ── Match 1 ──────────────────────────────────────────────────
-- Radiant team (5 players, positions 1-5)
INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000555001, (SELECT id FROM Hero WHERE name='Juggernaut'),        1, (SELECT id FROM Item WHERE name='Butterfly'), (SELECT id FROM Item WHERE name='Daedalus'), (SELECT id FROM Item WHERE name='Maelstrom'), (SELECT id FROM Item WHERE name='Power Treads'), (SELECT id FROM Item WHERE name='Battle Fury'), (SELECT id FROM Item WHERE name='Skull Basher'), 12, 3, 7, 24300);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000555002, (SELECT id FROM Hero WHERE name='Invoker'),           2, (SELECT id FROM Item WHERE name='Aghanim''s Scepter'), (SELECT id FROM Item WHERE name='Octarine Core'), (SELECT id FROM Item WHERE name='Eul''s Scepter of Divinity'), (SELECT id FROM Item WHERE name='Arcane Boots'), (SELECT id FROM Item WHERE name='Blink Dagger'), (SELECT id FROM Item WHERE name='Black King Bar'), 8, 5, 14, 19800);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000666001, (SELECT id FROM Hero WHERE name='Axe'),               3, (SELECT id FROM Item WHERE name='Blade Mail'), (SELECT id FROM Item WHERE name='Crimson Guard'), (SELECT id FROM Item WHERE name='Blink Dagger'), (SELECT id FROM Item WHERE name='Phase Boots'), (SELECT id FROM Item WHERE name='Black King Bar'), NULL, 6, 8, 11, 14200);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000777001, (SELECT id FROM Hero WHERE name='Rubick'),            4, (SELECT id FROM Item WHERE name='Aether Lens'), (SELECT id FROM Item WHERE name='Force Staff'), (SELECT id FROM Item WHERE name='Arcane Boots'), (SELECT id FROM Item WHERE name='Aghanim''s Scepter'), NULL, NULL, 3, 6, 18, 9700);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000888001, (SELECT id FROM Hero WHERE name='Crystal Maiden'),   5, (SELECT id FROM Item WHERE name='Glimmer Cape'), (SELECT id FROM Item WHERE name='Mekansm'), (SELECT id FROM Item WHERE name='Arcane Boots'), (SELECT id FROM Item WHERE name='Ghost Scepter'), NULL, NULL, 1, 9, 22, 7600);

-- Dire team (5 players)
INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000333001, (SELECT id FROM Hero WHERE name='Anti-Mage'),        1, (SELECT id FROM Item WHERE name='Manta Style'), (SELECT id FROM Item WHERE name='Butterfly'), (SELECT id FROM Item WHERE name='Battle Fury'), (SELECT id FROM Item WHERE name='Power Treads'), (SELECT id FROM Item WHERE name='Eye of Skadi'), (SELECT id FROM Item WHERE name='Abyssal Blade'), 7, 4, 2, 22100);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000444001, (SELECT id FROM Hero WHERE name='Shadow Fiend'),     2, (SELECT id FROM Item WHERE name='Daedalus'), (SELECT id FROM Item WHERE name='Black King Bar'), (SELECT id FROM Item WHERE name='Phase Boots'), (SELECT id FROM Item WHERE name='Shadow Amulet'), NULL, NULL, 5, 7, 9, 16400);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000222001, (SELECT id FROM Hero WHERE name='Tidehunter'),       3, (SELECT id FROM Item WHERE name='Pipe of Insight'), (SELECT id FROM Item WHERE name='Shiva''s Guard'), (SELECT id FROM Item WHERE name='Tranquil Boots'), NULL, NULL, NULL, 2, 10, 14, 11900);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000111001, (SELECT id FROM Hero WHERE name='Lion'),             4, (SELECT id FROM Item WHERE name='Aether Lens'), (SELECT id FROM Item WHERE name='Arcane Boots'), (SELECT id FROM Item WHERE name='Ghost Scepter'), NULL, NULL, NULL, 4, 11, 10, 8200);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000111002, (SELECT id FROM Hero WHERE name='Witch Doctor'),     5, (SELECT id FROM Item WHERE name='Glimmer Cape'), (SELECT id FROM Item WHERE name='Arcane Boots'), NULL, NULL, NULL, NULL, 2, 12, 9, 6800);

-- Build teams for Match 1
INSERT INTO Team (side, hp1, hp2, hp3, hp4, hp5)
VALUES ('Radiant',
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000555001 AND hero_id=(SELECT id FROM Hero WHERE name='Juggernaut')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000555002 AND hero_id=(SELECT id FROM Hero WHERE name='Invoker')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000666001 AND hero_id=(SELECT id FROM Hero WHERE name='Axe')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000777001 AND hero_id=(SELECT id FROM Hero WHERE name='Rubick')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000888001 AND hero_id=(SELECT id FROM Hero WHERE name='Crystal Maiden'))
);

INSERT INTO Team (side, hp1, hp2, hp3, hp4, hp5)
VALUES ('Dire',
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000333001 AND hero_id=(SELECT id FROM Hero WHERE name='Anti-Mage')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000444001 AND hero_id=(SELECT id FROM Hero WHERE name='Shadow Fiend')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000222001 AND hero_id=(SELECT id FROM Hero WHERE name='Tidehunter')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000111001 AND hero_id=(SELECT id FROM Hero WHERE name='Lion')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000111002 AND hero_id=(SELECT id FROM Hero WHERE name='Witch Doctor'))
);

-- Create Match 1 — Radiant wins
INSERT INTO Match_Game (match_time, team1_id, team2_id, winner_id, is_ranked)
VALUES (
    TIMESTAMP '2024-11-15 18:30:00',
    (SELECT id FROM Team WHERE side='Radiant' AND ROWNUM=1),
    (SELECT id FROM Team WHERE side='Dire'    AND ROWNUM=1),
    (SELECT id FROM Team WHERE side='Radiant' AND ROWNUM=1),
    1
);


-- ── Match 2 ──────────────────────────────────────────────────
-- Radiant team
INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000777002, (SELECT id FROM Hero WHERE name='Spectre'),          1, (SELECT id FROM Item WHERE name='Radiance'), (SELECT id FROM Item WHERE name='Manta Style'), (SELECT id FROM Item WHERE name='Diffusal Blade'), (SELECT id FROM Item WHERE name='Power Treads'), (SELECT id FROM Item WHERE name='Heart of Tarrasque'), NULL, 15, 2, 9, 27500);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000666002, (SELECT id FROM Hero WHERE name='Storm Spirit'),     2, (SELECT id FROM Item WHERE name='Bloodstone'), (SELECT id FROM Item WHERE name='Aghanim''s Scepter'), (SELECT id FROM Item WHERE name='Scythe of Vyse'), (SELECT id FROM Item WHERE name='Arcane Boots'), NULL, NULL, 11, 4, 13, 21000);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000444002, (SELECT id FROM Hero WHERE name='Mars'),             3, (SELECT id FROM Item WHERE name='Blink Dagger'), (SELECT id FROM Item WHERE name='Crimson Guard'), (SELECT id FROM Item WHERE name='Black King Bar'), (SELECT id FROM Item WHERE name='Phase Boots'), (SELECT id FROM Item WHERE name='Pipe of Insight'), NULL, 4, 6, 17, 13800);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000333002, (SELECT id FROM Hero WHERE name='Earthshaker'),      4, (SELECT id FROM Item WHERE name='Blink Dagger'), (SELECT id FROM Item WHERE name='Arcane Boots'), (SELECT id FROM Item WHERE name='Aether Lens'), NULL, NULL, NULL, 5, 8, 19, 10100);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000222002, (SELECT id FROM Hero WHERE name='Dazzle'),           5, (SELECT id FROM Item WHERE name='Guardian Greaves'), (SELECT id FROM Item WHERE name='Glimmer Cape'), (SELECT id FROM Item WHERE name='Rod of Atos'), NULL, NULL, NULL, 2, 7, 23, 8400);

-- Dire team
INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000888002, (SELECT id FROM Hero WHERE name='Phantom Assassin'), 1, (SELECT id FROM Item WHERE name='Daedalus'), (SELECT id FROM Item WHERE name='Butterfly'), (SELECT id FROM Item WHERE name='Black King Bar'), (SELECT id FROM Item WHERE name='Skull Basher'), (SELECT id FROM Item WHERE name='Phase Boots'), NULL, 9, 6, 5, 20800);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000555003, (SELECT id FROM Hero WHERE name='Lina'),             2, (SELECT id FROM Item WHERE name='Aghanim''s Scepter'), (SELECT id FROM Item WHERE name='Eul''s Scepter of Divinity'), (SELECT id FROM Item WHERE name='Arcane Boots'), (SELECT id FROM Item WHERE name='Dagon'), NULL, NULL, 7, 9, 10, 17200);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000666003, (SELECT id FROM Hero WHERE name='Bristleback'),      3, (SELECT id FROM Item WHERE name='Pipe of Insight'), (SELECT id FROM Item WHERE name='Crimson Guard'), (SELECT id FROM Item WHERE name='Shiva''s Guard'), (SELECT id FROM Item WHERE name='Phase Boots'), NULL, NULL, 3, 11, 12, 12300);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000777003, (SELECT id FROM Hero WHERE name='Disruptor'),        4, (SELECT id FROM Item WHERE name='Aether Lens'), (SELECT id FROM Item WHERE name='Force Staff'), (SELECT id FROM Item WHERE name='Arcane Boots'), NULL, NULL, NULL, 1, 13, 14, 9000);

INSERT INTO Hero_Played (steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
VALUES (76561198000888003, (SELECT id FROM Hero WHERE name='Lich'),             5, (SELECT id FROM Item WHERE name='Glimmer Cape'), (SELECT id FROM Item WHERE name='Arcane Boots'), (SELECT id FROM Item WHERE name='Ghost Scepter'), NULL, NULL, NULL, 0, 14, 16, 7100);

-- Build teams for Match 2
INSERT INTO Team (side, hp1, hp2, hp3, hp4, hp5)
VALUES ('Radiant',
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000777002 AND hero_id=(SELECT id FROM Hero WHERE name='Spectre')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000666002 AND hero_id=(SELECT id FROM Hero WHERE name='Storm Spirit')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000444002 AND hero_id=(SELECT id FROM Hero WHERE name='Mars')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000333002 AND hero_id=(SELECT id FROM Hero WHERE name='Earthshaker')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000222002 AND hero_id=(SELECT id FROM Hero WHERE name='Dazzle'))
);

INSERT INTO Team (side, hp1, hp2, hp3, hp4, hp5)
VALUES ('Dire',
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000888002 AND hero_id=(SELECT id FROM Hero WHERE name='Phantom Assassin')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000555003 AND hero_id=(SELECT id FROM Hero WHERE name='Lina')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000666003 AND hero_id=(SELECT id FROM Hero WHERE name='Bristleback')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000777003 AND hero_id=(SELECT id FROM Hero WHERE name='Disruptor')),
    (SELECT id FROM Hero_Played WHERE steam_id=76561198000888003 AND hero_id=(SELECT id FROM Hero WHERE name='Lich'))
);

-- Create Match 2 — Dire wins (unranked)
INSERT INTO Match_Game (match_time, team1_id, team2_id, winner_id, is_ranked)
VALUES (
    TIMESTAMP '2024-11-16 21:00:00',
    (SELECT id FROM Team WHERE side='Radiant' AND id=(SELECT MAX(id) FROM Team WHERE side='Radiant')),
    (SELECT id FROM Team WHERE side='Dire'    AND id=(SELECT MAX(id) FROM Team WHERE side='Dire')),
    (SELECT id FROM Team WHERE side='Dire'    AND id=(SELECT MAX(id) FROM Team WHERE side='Dire')),
    0
);

COMMIT;


-- ============================================================
--  VERIFICATION QUERIES
-- ============================================================

-- Row counts
SELECT 'Hero_Played' AS entity, COUNT(*) AS total FROM Hero_Played
UNION ALL
SELECT 'Team',       COUNT(*) FROM Team
UNION ALL
SELECT 'Match_Game', COUNT(*) FROM Match_Game;

-- Match summary with winner side
SELECT
    m.id                              AS match_id,
    TO_CHAR(m.match_time,'YYYY-MM-DD HH24:MI') AS played_at,
    t1.side                           AS team1_side,
    t2.side                           AS team2_side,
    tw.side                           AS winner,
    CASE m.is_ranked WHEN 1 THEN 'Ranked' ELSE 'Unranked' END AS game_type
FROM Match_Game m
JOIN Team t1 ON t1.id = m.team1_id
JOIN Team t2 ON t2.id = m.team2_id
JOIN Team tw ON tw.id = m.winner_id
ORDER BY m.id;

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
