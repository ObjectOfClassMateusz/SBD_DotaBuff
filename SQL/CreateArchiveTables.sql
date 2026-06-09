
BEGIN
    FOR t IN (SELECT table_name FROM user_tables
              WHERE table_name IN ('ARCHIVED_COLD_MATCH_GAME','ARCHIVED_COLD_TEAM','ARCHIVED_COLD_HERO_PLAYED',
                                   'ARCHIVED_WARM_MATCH_GAME','ARCHIVED_WARM_TEAM','ARCHIVED_WARM_HERO_PLAYED'))
    LOOP
        EXECUTE IMMEDIATE 'DROP TABLE ' || t.table_name || ' CASCADE CONSTRAINTS';
    END LOOP;
END;
/


CREATE TABLE Archived_Cold_Hero_Played (
    id        NUMBER        CONSTRAINT ac_pk_heroplayed PRIMARY KEY,

    -- Who played
    steam_id  NUMBER(17)    CONSTRAINT ac_nn_hp_steam NOT NULL,
                            CONSTRAINT ac_fk_hp_player
                                FOREIGN KEY (steam_id) REFERENCES Player (steam_id),

    -- Which hero
    hero_id   NUMBER        CONSTRAINT ac_nn_hp_hero NOT NULL,
                            CONSTRAINT ac_fk_hp_hero
                                FOREIGN KEY (hero_id)  REFERENCES Hero (id),

    -- Position on the map (1 = carry ... 5 = hard support)
    position  NUMBER(1)     CONSTRAINT ac_nn_hp_pos NOT NULL,
                            CONSTRAINT ac_ck_hp_pos CHECK (position BETWEEN 1 AND 5),

    -- Item slots (NULL = empty slot)
    slot1     NUMBER        CONSTRAINT ac_fk_hp_slot1 REFERENCES Item (id),
    slot2     NUMBER        CONSTRAINT ac_fk_hp_slot2 REFERENCES Item (id),
    slot3     NUMBER        CONSTRAINT ac_fk_hp_slot3 REFERENCES Item (id),
    slot4     NUMBER        CONSTRAINT ac_fk_hp_slot4 REFERENCES Item (id),
    slot5     NUMBER        CONSTRAINT ac_fk_hp_slot5 REFERENCES Item (id),
    slot6     NUMBER        CONSTRAINT ac_fk_hp_slot6 REFERENCES Item (id),

    -- Performance stats
    kills     NUMBER(3)     DEFAULT 0 CONSTRAINT ac_nn_hp_kills NOT NULL,
    deaths    NUMBER(3)     DEFAULT 0 CONSTRAINT ac_nn_hp_deaths NOT NULL,
    assists   NUMBER(3)     DEFAULT 0 CONSTRAINT ac_nn_hp_assists NOT NULL,

    -- Net worth in gold
    netto     NUMBER(7)     DEFAULT 0 CONSTRAINT ac_nn_hp_netto NOT NULL,

    -- Derived KDA stored for fast querying
    -- Formula: (kills + assists) / GREATEST(deaths, 1)
    kda       NUMBER(6,2)   GENERATED ALWAYS AS (
                  (kills + assists) / GREATEST(deaths, 1)
              ) VIRTUAL
);


CREATE TABLE Archived_Cold_Team (
    id    NUMBER       CONSTRAINT ac_pk_team PRIMARY KEY,

    side  VARCHAR2(7)  CONSTRAINT ac_nn_team_side NOT NULL,
                       CONSTRAINT ac_ck_team_side CHECK (side IN ('Radiant','Dire')),

    -- Five player slots — all required for a valid Dota 2 team
    hp1   NUMBER       CONSTRAINT ac_nn_team_hp1 NOT NULL,
                       CONSTRAINT ac_fk_team_hp1 FOREIGN KEY (hp1) REFERENCES Archived_Cold_Hero_Played (id),

    hp2   NUMBER       CONSTRAINT ac_nn_team_hp2 NOT NULL,
                       CONSTRAINT ac_fk_team_hp2 FOREIGN KEY (hp2) REFERENCES Archived_Cold_Hero_Played (id),

    hp3   NUMBER       CONSTRAINT ac_nn_team_hp3 NOT NULL,
                       CONSTRAINT ac_fk_team_hp3 FOREIGN KEY (hp3) REFERENCES Archived_Cold_Hero_Played (id),

    hp4   NUMBER       CONSTRAINT ac_nn_team_hp4 NOT NULL,
                       CONSTRAINT ac_fk_team_hp4 FOREIGN KEY (hp4) REFERENCES Archived_Cold_Hero_Played (id),

    hp5   NUMBER       CONSTRAINT ac_nn_team_hp5 NOT NULL,
                       CONSTRAINT ac_fk_team_hp5 FOREIGN KEY (hp5) REFERENCES Archived_Cold_Hero_Played (id),

    -- Each Hero_Played row belongs to exactly one team
    CONSTRAINT ac_uq_team_hp1 UNIQUE (hp1),
    CONSTRAINT ac_uq_team_hp2 UNIQUE (hp2),
    CONSTRAINT ac_uq_team_hp3 UNIQUE (hp3),
    CONSTRAINT ac_uq_team_hp4 UNIQUE (hp4),
    CONSTRAINT ac_uq_team_hp5 UNIQUE (hp5)
);


CREATE TABLE Archived_Cold_Match_Game (
    id         NUMBER         CONSTRAINT ac_pk_match PRIMARY KEY,

    -- When the match was played
    match_time TIMESTAMP      CONSTRAINT ac_nn_match_time NOT NULL,

    -- The two competing teams
    team1_id   NUMBER         CONSTRAINT ac_nn_match_t1 NOT NULL,
                              CONSTRAINT ac_fk_match_team1
                                  FOREIGN KEY (team1_id) REFERENCES Archived_Cold_Team (id),

    team2_id   NUMBER         CONSTRAINT ac_nn_match_t2 NOT NULL,
                              CONSTRAINT ac_fk_match_team2
                                  FOREIGN KEY (team2_id) REFERENCES Archived_Cold_Team (id),

    -- Winner — must be one of the two teams
    winner_id  NUMBER         CONSTRAINT ac_nn_match_winner NOT NULL,
                              CONSTRAINT ac_fk_match_winner
                                  FOREIGN KEY (winner_id) REFERENCES Archived_Cold_Team (id),

    -- Ranked or unranked game
    is_ranked  NUMBER(1)      CONSTRAINT ac_nn_match_ranked NOT NULL,
                              CONSTRAINT ac_ck_match_ranked CHECK (is_ranked IN (0, 1)),

    -- Teams must be different
    CONSTRAINT ac_ck_match_teams CHECK (team1_id <> team2_id),

    -- Winner must be one of the two participating teams
    CONSTRAINT ac_ck_match_winner CHECK (
        winner_id = team1_id OR winner_id = team2_id
    )
);



CREATE TABLE Archived_Warm_Hero_Played (
    id        NUMBER        CONSTRAINT aw_pk_heroplayed PRIMARY KEY,

    -- Who played
    steam_id  NUMBER(17)    CONSTRAINT aw_nn_hp_steam NOT NULL,
                            CONSTRAINT aw_fk_hp_player
                                FOREIGN KEY (steam_id) REFERENCES Player (steam_id),

    -- Which hero
    hero_id   NUMBER        CONSTRAINT aw_nn_hp_hero NOT NULL,
                            CONSTRAINT aw_fk_hp_hero
                                FOREIGN KEY (hero_id)  REFERENCES Hero (id),

    -- Position on the map (1 = carry ... 5 = hard support)
    position  NUMBER(1)     CONSTRAINT aw_nn_hp_pos NOT NULL,
                            CONSTRAINT aw_ck_hp_pos CHECK (position BETWEEN 1 AND 5),

    -- Item slots (NULL = empty slot)
    slot1     NUMBER        CONSTRAINT aw_fk_hp_slot1 REFERENCES Item (id),
    slot2     NUMBER        CONSTRAINT aw_fk_hp_slot2 REFERENCES Item (id),
    slot3     NUMBER        CONSTRAINT aw_fk_hp_slot3 REFERENCES Item (id),
    slot4     NUMBER        CONSTRAINT aw_fk_hp_slot4 REFERENCES Item (id),
    slot5     NUMBER        CONSTRAINT aw_fk_hp_slot5 REFERENCES Item (id),
    slot6     NUMBER        CONSTRAINT aw_fk_hp_slot6 REFERENCES Item (id),

    -- Performance stats
    kills     NUMBER(3)     DEFAULT 0 CONSTRAINT aw_nn_hp_kills NOT NULL,
    deaths    NUMBER(3)     DEFAULT 0 CONSTRAINT aw_nn_hp_deaths NOT NULL,
    assists   NUMBER(3)     DEFAULT 0 CONSTRAINT aw_nn_hp_assists NOT NULL,

    -- Net worth in gold
    netto     NUMBER(7)     DEFAULT 0 CONSTRAINT aw_nn_hp_netto NOT NULL,

    -- Derived KDA stored for fast querying
    -- Formula: (kills + assists) / GREATEST(deaths, 1)
    kda       NUMBER(6,2)   GENERATED ALWAYS AS (
                  (kills + assists) / GREATEST(deaths, 1)
              ) VIRTUAL
);


CREATE TABLE Archived_Warm_Team (
    id    NUMBER       CONSTRAINT aw_pk_team PRIMARY KEY,

    side  VARCHAR2(7)  CONSTRAINT aw_nn_team_side NOT NULL,
                       CONSTRAINT aw_ck_team_side CHECK (side IN ('Radiant','Dire')),

    -- Five player slots — all required for a valid Dota 2 team
    hp1   NUMBER       CONSTRAINT aw_nn_team_hp1 NOT NULL,
                       CONSTRAINT aw_fk_team_hp1 FOREIGN KEY (hp1) REFERENCES Archived_Warm_Hero_Played (id),

    hp2   NUMBER       CONSTRAINT aw_nn_team_hp2 NOT NULL,
                       CONSTRAINT aw_fk_team_hp2 FOREIGN KEY (hp2) REFERENCES Archived_Warm_Hero_Played (id),

    hp3   NUMBER       CONSTRAINT aw_nn_team_hp3 NOT NULL,
                       CONSTRAINT aw_fk_team_hp3 FOREIGN KEY (hp3) REFERENCES Archived_Warm_Hero_Played (id),

    hp4   NUMBER       CONSTRAINT aw_nn_team_hp4 NOT NULL,
                       CONSTRAINT aw_fk_team_hp4 FOREIGN KEY (hp4) REFERENCES Archived_Warm_Hero_Played (id),

    hp5   NUMBER       CONSTRAINT aw_nn_team_hp5 NOT NULL,
                       CONSTRAINT aw_fk_team_hp5 FOREIGN KEY (hp5) REFERENCES Archived_Warm_Hero_Played (id),

    -- Each Hero_Played row belongs to exactly one team
    CONSTRAINT aw_uq_team_hp1 UNIQUE (hp1),
    CONSTRAINT aw_uq_team_hp2 UNIQUE (hp2),
    CONSTRAINT aw_uq_team_hp3 UNIQUE (hp3),
    CONSTRAINT aw_uq_team_hp4 UNIQUE (hp4),
    CONSTRAINT aw_uq_team_hp5 UNIQUE (hp5)
);


CREATE TABLE Archived_Warm_Match_Game (
    id         NUMBER         CONSTRAINT aw_pk_match PRIMARY KEY,

    -- When the match was played
    match_time TIMESTAMP      CONSTRAINT aw_nn_match_time NOT NULL,

    -- The two competing teams
    team1_id   NUMBER         CONSTRAINT aw_nn_match_t1 NOT NULL,
                              CONSTRAINT aw_fk_match_team1
                                  FOREIGN KEY (team1_id) REFERENCES Archived_Warm_Team (id),

    team2_id   NUMBER         CONSTRAINT aw_nn_match_t2 NOT NULL,
                              CONSTRAINT aw_fk_match_team2
                                  FOREIGN KEY (team2_id) REFERENCES Archived_Warm_Team (id),

    -- Winner — must be one of the two teams
    winner_id  NUMBER         CONSTRAINT aw_nn_match_winner NOT NULL,
                              CONSTRAINT aw_fk_match_winner
                                  FOREIGN KEY (winner_id) REFERENCES Archived_Warm_Team (id),

    -- Ranked or unranked game
    is_ranked  NUMBER(1)      CONSTRAINT aw_nn_match_ranked NOT NULL,
                              CONSTRAINT aw_ck_match_ranked CHECK (is_ranked IN (0, 1)),

    -- Teams must be different
    CONSTRAINT aw_ck_match_teams CHECK (team1_id <> team2_id),

    -- Winner must be one of the two participating teams
    CONSTRAINT aw_ck_match_winner CHECK (
        winner_id = team1_id OR winner_id = team2_id
    )
);



COMMIT;

