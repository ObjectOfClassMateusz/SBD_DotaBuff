
CREATE OR REPLACE TYPE HeroPlayedDTO AS OBJECT (
    steam_id  NUMBER(17),
    hero_id   NUMBER,
    position  NUMBER(1),

    -- Item slots (NULL = empty slot)
    slot1     NUMBER,
    slot2     NUMBER,
    slot3     NUMBER,
    slot4     NUMBER,
    slot5     NUMBER,
    slot6     NUMBER,

    -- Performance stats
    kills     NUMBER(3),
    deaths    NUMBER(3),
    assists   NUMBER(3),

    -- Net worth in gold
    netto     NUMBER(7)
);
/

CREATE OR REPLACE TYPE TeamDTO AS OBJECT(
    side  VARCHAR2(7),

    -- Five player slots — all required for a valid Dota 2 team
    hp1   HeroPlayedDTO,
    hp2   HeroPlayedDTO,
    hp3   HeroPlayedDTO,
    hp4   HeroPlayedDTO,
    hp5   HeroPlayedDTO
);
/


CREATE OR REPLACE PACKAGE DatabaseAdministration
AS
    
    invalid_patch_date_exception EXCEPTION;
    PRAGMA exception_init(invalid_patch_date_exception, -20111);
    
    no_patch_exception EXCEPTION;
    PRAGMA exception_init(no_patch_exception, -20112);
    
    invalid_match_data_exception EXCEPTION;
    PRAGMA exception_init(invalid_match_data_exception, -20113);
    
    
    PROCEDURE RegisterMatch(v_time IN TIMESTAMP,
                            v_team1 IN TeamDTO,
                            v_team2 IN TeamDTO,
                            v_winner IN NUMBER, -- 1 lub 2
                            v_is_ranked IN NUMBER);


    PROCEDURE AddNewHero(v_name IN Hero.name%TYPE,
                         v_attribute IN Hero.primary_attribute%TYPE);
    
    PROCEDURE AddNewItem(v_name IN Item.name%TYPE);
    
    PROCEDURE AddPlayer(v_steamId IN Player.steam_id%TYPE,
                        v_nick IN Player.nickname%TYPE,
                        v_region IN Player.region%TYPE,
                        v_date IN DATE);
                        
    
    PROCEDURE NewPatch(v_version_string IN Patch_Info.version_string%TYPE,
                       v_date IN DATE);
                       
    PROCEDURE PatchHero(v_heroId IN Hero.id%TYPE,
                        v_change_flag IN Patch_Hero_Change.change_flag%TYPE,
                        v_change_desc IN Patch_Hero_Change.change_description%TYPE);
    
    PROCEDURE PatchItem(v_itemId IN Item.id%TYPE,
                        v_change_flag IN Patch_Hero_Change.change_flag%TYPE,
                        v_change_desc IN Patch_Hero_Change.change_description%TYPE);
                        
                        
    PROCEDURE ArchiveWarm;
    
    PROCEDURE ArchiveCold;

END DatabaseAdministration;
/


CREATE OR REPLACE PACKAGE BODY DatabaseAdministration
AS

    PROCEDURE RegisterMatch(v_time IN TIMESTAMP,
                            v_team1 IN TeamDTO,
                            v_team2 IN TeamDTO,
                            v_winner IN NUMBER, -- 1 lub 2
                            v_is_ranked IN NUMBER)
    AS
        v_t1_hp1_id Hero_Played.id%TYPE;
        v_t1_hp2_id Hero_Played.id%TYPE;
        v_t1_hp3_id Hero_Played.id%TYPE;
        v_t1_hp4_id Hero_Played.id%TYPE;
        v_t1_hp5_id Hero_Played.id%TYPE;
        
        v_t2_hp1_id Hero_Played.id%TYPE;
        v_t2_hp2_id Hero_Played.id%TYPE;
        v_t2_hp3_id Hero_Played.id%TYPE;
        v_t2_hp4_id Hero_Played.id%TYPE;
        v_t2_hp5_id Hero_Played.id%TYPE;
        
        v_t1_id Team.id%TYPE;
        v_t2_id Team.id%TYPE;
        
        v_winnerId NUMBER(1);
    BEGIN
        IF v_winner != 1 AND v_winner !=2 THEN
            raise_application_error(-20113, 'Winner must be team 1 or team 2.');
        END IF;
        
        v_t1_hp1_id := seq_heroplayed_id.NEXTVAL;
        v_t1_hp2_id := seq_heroplayed_id.NEXTVAL;
        v_t1_hp3_id := seq_heroplayed_id.NEXTVAL;
        v_t1_hp4_id := seq_heroplayed_id.NEXTVAL;
        v_t1_hp5_id := seq_heroplayed_id.NEXTVAL;
        
        v_t2_hp1_id := seq_heroplayed_id.NEXTVAL;
        v_t2_hp2_id := seq_heroplayed_id.NEXTVAL;
        v_t2_hp3_id := seq_heroplayed_id.NEXTVAL;
        v_t2_hp4_id := seq_heroplayed_id.NEXTVAL;
        v_t2_hp5_id := seq_heroplayed_id.NEXTVAL;
        
        v_t1_id := seq_team_id.NEXTVAL;
        v_t2_id := seq_team_id.NEXTVAL;
        
        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t1_hp1_id, v_team1.hp1.steam_id, v_team1.hp1.hero_id, v_team1.hp1.position, v_team1.hp1.slot1, v_team1.hp1.slot2, v_team1.hp1.slot3, v_team1.hp1.slot4, v_team1.hp1.slot5, v_team1.hp1.slot6, v_team1.hp1.kills, v_team1.hp1.deaths, v_team1.hp1.assists, v_team1.hp1.netto);

        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t1_hp2_id, v_team1.hp2.steam_id, v_team1.hp2.hero_id, v_team1.hp2.position, v_team1.hp2.slot1, v_team1.hp2.slot2, v_team1.hp2.slot3, v_team1.hp2.slot4, v_team1.hp2.slot5, v_team1.hp2.slot6, v_team1.hp2.kills, v_team1.hp2.deaths, v_team1.hp2.assists, v_team1.hp2.netto);
            
        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t1_hp3_id, v_team1.hp3.steam_id, v_team1.hp3.hero_id, v_team1.hp3.position, v_team1.hp3.slot1, v_team1.hp3.slot2, v_team1.hp3.slot3, v_team1.hp3.slot4, v_team1.hp3.slot5, v_team1.hp3.slot6, v_team1.hp3.kills, v_team1.hp3.deaths, v_team1.hp3.assists, v_team1.hp3.netto);
            
        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t1_hp4_id, v_team1.hp4.steam_id, v_team1.hp4.hero_id, v_team1.hp4.position, v_team1.hp4.slot1, v_team1.hp4.slot2, v_team1.hp4.slot3, v_team1.hp4.slot4, v_team1.hp4.slot5, v_team1.hp4.slot6, v_team1.hp4.kills, v_team1.hp4.deaths, v_team1.hp4.assists, v_team1.hp4.netto);
            
        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t1_hp5_id, v_team1.hp5.steam_id, v_team1.hp5.hero_id, v_team1.hp5.position, v_team1.hp5.slot1, v_team1.hp5.slot2, v_team1.hp5.slot3, v_team1.hp5.slot4, v_team1.hp5.slot5, v_team1.hp5.slot6, v_team1.hp5.kills, v_team1.hp5.deaths, v_team1.hp5.assists, v_team1.hp5.netto);
        
        
        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t2_hp1_id, v_team2.hp1.steam_id, v_team2.hp1.hero_id, v_team2.hp1.position, v_team2.hp1.slot1, v_team2.hp1.slot2, v_team2.hp1.slot3, v_team2.hp1.slot4, v_team2.hp1.slot5, v_team2.hp1.slot6, v_team2.hp1.kills, v_team2.hp1.deaths, v_team2.hp1.assists, v_team2.hp1.netto);

        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t2_hp2_id, v_team2.hp2.steam_id, v_team2.hp2.hero_id, v_team2.hp2.position, v_team2.hp2.slot1, v_team2.hp2.slot2, v_team2.hp2.slot3, v_team2.hp2.slot4, v_team2.hp2.slot5, v_team2.hp2.slot6, v_team2.hp2.kills, v_team2.hp2.deaths, v_team2.hp2.assists, v_team2.hp2.netto);
            
        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t2_hp3_id, v_team2.hp3.steam_id, v_team2.hp3.hero_id, v_team2.hp3.position, v_team2.hp3.slot1, v_team2.hp3.slot2, v_team2.hp3.slot3, v_team2.hp3.slot4, v_team2.hp3.slot5, v_team2.hp3.slot6, v_team2.hp3.kills, v_team2.hp3.deaths, v_team2.hp3.assists, v_team2.hp3.netto);
            
        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t2_hp4_id, v_team2.hp4.steam_id, v_team2.hp4.hero_id, v_team2.hp4.position, v_team2.hp4.slot1, v_team2.hp4.slot2, v_team2.hp4.slot3, v_team2.hp4.slot4, v_team2.hp4.slot5, v_team2.hp4.slot6, v_team2.hp4.kills, v_team2.hp4.deaths, v_team2.hp4.assists, v_team2.hp4.netto);
            
        INSERT INTO Hero_Played (id, steam_id, hero_id, position, slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto) VALUES
            (v_t2_hp5_id, v_team2.hp5.steam_id, v_team2.hp5.hero_id, v_team2.hp5.position, v_team2.hp5.slot1, v_team2.hp5.slot2, v_team2.hp5.slot3, v_team2.hp5.slot4, v_team2.hp5.slot5, v_team2.hp5.slot6, v_team2.hp5.kills, v_team2.hp5.deaths, v_team2.hp5.assists, v_team2.hp5.netto);
        
        
        INSERT INTO Team (id, side, hp1, hp2, hp3, hp4, hp5) VALUES (v_t1_id, v_team1.side, v_t1_hp1_id, v_t1_hp2_id, v_t1_hp3_id, v_t1_hp4_id, v_t1_hp5_id);
        
        INSERT INTO Team (id, side, hp1, hp2, hp3, hp4, hp5) VALUES (v_t2_id, v_team2.side, v_t2_hp1_id, v_t2_hp2_id, v_t2_hp3_id, v_t2_hp4_id, v_t2_hp5_id);
        
        
        IF v_winner = 1 THEN
            v_winnerId := v_t1_id;
        ELSIF v_winner = 2 THEN
            v_winnerId := v_t2_id;
        END IF;
        
        
        INSERT INTO Match_Game (match_time, team1_id, team2_id, winner_id, is_ranked) VALUES (v_time, v_t1_id, v_t2_id, v_winnerId, v_is_ranked);
        
        COMMIT;
        
    EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
    END;


    PROCEDURE AddNewHero(v_name IN Hero.name%TYPE,
                         v_attribute IN Hero.primary_attribute%TYPE)
    AS
    BEGIN
        INSERT INTO Hero (name, primary_attribute) VALUES (v_name, v_attribute);
    END;
    
    
    PROCEDURE AddNewItem(v_name IN Item.name%TYPE)
    AS
    BEGIN
        INSERT INTO Item (name) VALUES (v_name);
    END;
    
    PROCEDURE AddPlayer(v_steamId IN Player.steam_id%TYPE,
                        v_nick IN Player.nickname%TYPE,
                        v_region IN Player.region%TYPE,
                        v_date IN DATE)
    AS
    BEGIN
        INSERT INTO Player (steam_id, nickname, region, account_created, rank) VALUES (v_steamId, v_nick, v_region, v_date, 'Herald');
    END;
    
    
    
    PROCEDURE NewPatch(v_version_string IN Patch_Info.version_string%TYPE,
                       v_date IN DATE)
    AS
        v_lastPatchDate DATE;
        v_anyPatch NUMBER;
    BEGIN
        
        SELECT count(id) INTO v_anyPatch
        FROM Patch_Info;
        
        IF v_anyPatch >= 1 THEN
            
            SELECT max(release_date) INTO v_lastPatchDate
            FROM Patch_Info;
            
            IF v_date <= v_lastPatchDate THEN
                raise_application_error(-20111, 'Release date of a new patch must be after the release of any previous patch.');
            END IF;
            
        END IF;
        
        INSERT INTO Patch_Info (version_string, release_date) VALUES (v_version_string, v_date);
        
    END;
                       
    PROCEDURE PatchHero(v_heroId IN Hero.id%TYPE,
                        v_change_flag IN Patch_Hero_Change.change_flag%TYPE,
                        v_change_desc IN Patch_Hero_Change.change_description%TYPE)
    AS
        v_anyPatch NUMBER;
        
        v_patchId Patch_Info.id%TYPE;
    BEGIN
        SELECT count(id) INTO v_anyPatch
        FROM Patch_Info;
        
        IF v_anyPatch = 0 THEN
            raise_application_error(-20112, 'A patch must be created before adding hero patch notes.');
        END IF;
        
        SELECT id INTO v_patchId
        FROM Patch_Info
        WHERE release_date = (SELECT max(release_date)
                              FROM Patch_Info);
                              
        INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES (v_patchId, v_heroId, v_change_flag, v_change_desc);
    END;
    
    PROCEDURE PatchItem(v_itemId IN Item.id%TYPE,
                        v_change_flag IN Patch_Hero_Change.change_flag%TYPE,
                        v_change_desc IN Patch_Hero_Change.change_description%TYPE)
    AS
        v_anyPatch NUMBER;
        
        v_patchId Patch_Info.id%TYPE;
    BEGIN
        SELECT count(id) INTO v_anyPatch
        FROM Patch_Info;
        
        IF v_anyPatch = 0 THEN
            raise_application_error(-20112, 'A patch must be created before adding item patch notes.');
        END IF;
        
        SELECT id INTO v_patchId
        FROM Patch_Info
        WHERE release_date = (SELECT max(release_date)
                              FROM Patch_Info);
                              
        INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES (v_patchId, v_itemId, v_change_flag, v_change_desc);
    END;
    
    
    
    PROCEDURE ArchiveWarm
    AS
        v_count NUMBER;
    BEGIN
        FOR v_rec IN (SELECT *
                      FROM Hero_Played hp
                      WHERE EXISTS (SELECT 1
                                    FROM Match_Game m, Team t
                                    WHERE (m.team1_id = t.id OR m.team2_id = t.id) AND
                                          (t.hp1 = hp.id OR
                                           t.hp2 = hp.id OR
                                           t.hp3 = hp.id OR
                                           t.hp4 = hp.id OR
                                           t.hp5 = hp.id) AND
                                          (TRUNC(match_time) + 30) < SYSDATE))
        LOOP
            
            SELECT count(id) INTO v_count
            FROM Archived_Warm_Hero_Played
            WHERE id = v_rec.id;
            
            IF v_count = 0 THEN
                INSERT INTO Archived_Warm_Hero_Played (id, steam_id, hero_id, position,
                    slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
                    VALUES (v_rec.id, v_rec.steam_id, v_rec.hero_id, v_rec.position,
                    v_rec.slot1, v_rec.slot2, v_rec.slot3, v_rec.slot4, v_rec.slot5, v_rec.slot6, v_rec.kills, v_rec.deaths, v_rec.assists, v_rec.netto);
            END IF;
            
        END LOOP;
        
        
        FOR v_rec IN (SELECT *
                      FROM Team t
                      WHERE EXISTS (SELECT 1
                                    FROM Match_Game m
                                    WHERE (m.team1_id = t.id OR m.team2_id = t.id) AND
                                          (TRUNC(match_time) + 30) < SYSDATE))
        LOOP
            
            SELECT count(id) INTO v_count
            FROM Archived_Warm_Team
            WHERE id = v_rec.id;
            
            IF v_count = 0 THEN
                INSERT INTO Archived_Warm_Team (id, side, hp1, hp2, hp3, hp4, hp5)
                VALUES (v_rec.id, v_rec.side, v_rec.hp1, v_rec.hp2, v_rec.hp3, v_rec.hp4, v_rec.hp5);
            END IF;
            
        END LOOP;
        
        
        FOR v_rec IN (SELECT *
                      FROM Match_Game
                      WHERE (TRUNC(match_time) + 30) < SYSDATE)
        LOOP
            
            SELECT count(id) INTO v_count
            FROM Archived_Warm_Match_Game
            WHERE id = v_rec.id;
            
            IF v_count = 0 THEN
                INSERT INTO Archived_Warm_Match_Game (id, match_time, team1_id, team2_id, winner_id, is_ranked)
                VALUES (v_rec.id, v_rec.match_time, v_rec.team1_id, v_rec.team2_id, v_rec.winner_id, v_rec.is_ranked);
            END IF;
            
        END LOOP;
        
        
        COMMIT;
    EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
    END;
    
    PROCEDURE ArchiveCold
    AS
        v_count NUMBER;
    BEGIN
        FOR v_rec IN (SELECT *
                      FROM Hero_Played hp
                      WHERE EXISTS (SELECT 1
                                    FROM Match_Game m, Team t
                                    WHERE (m.team1_id = t.id OR m.team2_id = t.id) AND
                                          (t.hp1 = hp.id OR
                                           t.hp2 = hp.id OR
                                           t.hp3 = hp.id OR
                                           t.hp4 = hp.id OR
                                           t.hp5 = hp.id) AND
                                          (TRUNC(match_time) + 365) < SYSDATE))
        LOOP
            
            SELECT count(id) INTO v_count
            FROM Archived_Cold_Hero_Played
            WHERE id = v_rec.id;
            
            IF v_count = 0 THEN
                INSERT INTO Archived_Cold_Hero_Played (id, steam_id, hero_id, position,
                    slot1, slot2, slot3, slot4, slot5, slot6, kills, deaths, assists, netto)
                    VALUES (v_rec.id, v_rec.steam_id, v_rec.hero_id, v_rec.position,
                    v_rec.slot1, v_rec.slot2, v_rec.slot3, v_rec.slot4, v_rec.slot5, v_rec.slot6, v_rec.kills, v_rec.deaths, v_rec.assists, v_rec.netto);
            END IF;
            
        END LOOP;
        
        
        FOR v_rec IN (SELECT *
                      FROM Team t
                      WHERE EXISTS (SELECT 1
                                    FROM Match_Game m
                                    WHERE (m.team1_id = t.id OR m.team2_id = t.id) AND
                                          (TRUNC(match_time) + 365) < SYSDATE))
        LOOP
            
            SELECT count(id) INTO v_count
            FROM Archived_Cold_Team
            WHERE id = v_rec.id;
            
            IF v_count = 0 THEN
                INSERT INTO Archived_Cold_Team (id, side, hp1, hp2, hp3, hp4, hp5)
                VALUES (v_rec.id, v_rec.side, v_rec.hp1, v_rec.hp2, v_rec.hp3, v_rec.hp4, v_rec.hp5);
            END IF;
            
        END LOOP;
        
        
        FOR v_rec IN (SELECT *
                      FROM Match_Game
                      WHERE (TRUNC(match_time) + 365) < SYSDATE)
        LOOP
            
            SELECT count(id) INTO v_count
            FROM Archived_Cold_Match_Game
            WHERE id = v_rec.id;
            
            IF v_count = 0 THEN
                INSERT INTO Archived_Cold_Match_Game (id, match_time, team1_id, team2_id, winner_id, is_ranked)
                VALUES (v_rec.id, v_rec.match_time, v_rec.team1_id, v_rec.team2_id, v_rec.winner_id, v_rec.is_ranked);
            END IF;
            
        END LOOP;
        
        
        COMMIT;
    EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
    END;
    

END DatabaseAdministration;
/


COMMIT;


DECLARE
    v_dazzleId Hero.id%TYPE;
    v_bloodstoneId Item.id%TYPE;
BEGIN
    DatabaseAdministration.NewPatch('7.43', TO_DATE('2026-06-26'));
    
    DatabaseAdministration.AddNewHero('Michael', 'Intelligence');
    
    DatabaseAdministration.AddNewItem('Axe Wand');
    
    SELECT id INTO v_dazzleId FROM Hero WHERE name='Dazzle';
    DatabaseAdministration.PatchHero(v_dazzleId, 1, 'Increased max HP');
    
    SELECT id INTO v_bloodstoneId FROM Item WHERE name='Bloodstone';
    DatabaseAdministration.PatchItem(v_bloodstoneId, 2, 'Decreased bonus strength');
    
    UPDATE Hero
    SET primary_attribute='Strength'
    WHERE name='Bane';
    
    UPDATE Item
    SET name='Manta Style'
    WHERE name='Manta Styles';
END;
/

SELECT * FROM table(GeneralInfo.GetPatchHeroChanges(GeneralInfo.GetLatestPatchId));
SELECT * FROM table(GeneralInfo.GetPatchItemChanges(GeneralInfo.GetLatestPatchId));


DECLARE
    v_team1 TeamDTO := TeamDTO('Radiant', NULL, NULL, NULL, NULL, NULL);
    v_team2 TeamDTO := TeamDTO('Dire', NULL, NULL, NULL, NULL, NULL);
    
    v_heroId NUMBER;
    v_item1Id NUMBER;
    v_item2Id NUMBER;
    v_item3Id NUMBER;
    v_item4Id NUMBER;
    v_item5Id NUMBER;
BEGIN
    SELECT id INTO v_heroId FROM Hero WHERE name='Spectre';
    SELECT id INTO v_item1Id FROM Item WHERE name='Radiance';
    SELECT id INTO v_item2Id FROM Item WHERE name='Manta Style';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team1.hp1 := HeroPlayedDTO(76561198000444004, v_heroId, 1, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 15, 4, 9, 27500);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Tusk';
    SELECT id INTO v_item1Id FROM Item WHERE name='Necronomicon';
    SELECT id INTO v_item2Id FROM Item WHERE name='Urn of Shadows';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team1.hp2 := HeroPlayedDTO(76561198000111003, v_heroId, 2, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 17, 1, 7, 28000);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Slardar';
    SELECT id INTO v_item1Id FROM Item WHERE name='Kaya';
    SELECT id INTO v_item2Id FROM Item WHERE name='Claymore';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team1.hp3 := HeroPlayedDTO(76561198000888003, v_heroId, 3, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 12, 5, 10, 34000);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Bane';
    SELECT id INTO v_item1Id FROM Item WHERE name='Sange';
    SELECT id INTO v_item2Id FROM Item WHERE name='Iron Branch';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team1.hp4 := HeroPlayedDTO(76561198000555003, v_heroId, 4, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 10, 3, 11, 20000);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Dazzle';
    SELECT id INTO v_item1Id FROM Item WHERE name='Yasha';
    SELECT id INTO v_item2Id FROM Item WHERE name='Bloodstone';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team1.hp5 := HeroPlayedDTO(76561198000666002, v_heroId, 5, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 9, 2, 13, 22500);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Axe';
    SELECT id INTO v_item1Id FROM Item WHERE name='Mjollnir';
    SELECT id INTO v_item2Id FROM Item WHERE name='Hood of Defiance';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team2.hp1 := HeroPlayedDTO(76561198000111004, v_heroId, 1, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 20, 6, 4, 44100);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Doom';
    SELECT id INTO v_item1Id FROM Item WHERE name='Daedalus';
    SELECT id INTO v_item2Id FROM Item WHERE name='Manta Style';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team2.hp2 := HeroPlayedDTO(76561198000222005, v_heroId, 2, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 12, 3, 9, 1600);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Lina';
    SELECT id INTO v_item1Id FROM Item WHERE name='Kaya';
    SELECT id INTO v_item2Id FROM Item WHERE name='Battle Fury';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team2.hp3 := HeroPlayedDTO(76561198000333004, v_heroId, 3, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 7, 4, 8, 18354);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Visage';
    SELECT id INTO v_item1Id FROM Item WHERE name='Radiance';
    SELECT id INTO v_item2Id FROM Item WHERE name='Manta Style';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team2.hp4 := HeroPlayedDTO(76561198000777002, v_heroId, 4, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 8, 1, 11, 1789);
    
    SELECT id INTO v_heroId FROM Hero WHERE name='Puck';
    SELECT id INTO v_item1Id FROM Item WHERE name='Desolator';
    SELECT id INTO v_item2Id FROM Item WHERE name='Parasma';
    SELECT id INTO v_item3Id FROM Item WHERE name='Diffusal Blade';
    SELECT id INTO v_item4Id FROM Item WHERE name='Power Treads';
    SELECT id INTO v_item5Id FROM Item WHERE name='Heart of Tarrasque';
    v_team2.hp5 := HeroPlayedDTO(76561198000777001, v_heroId, 5, v_item1Id, v_item2Id, v_item3Id, v_item4Id, v_item5Id, NULL, 15, 7, 9, 9000);
    
    DatabaseAdministration.RegisterMatch(TIMESTAMP '2026-05-10 21:00:00',
                                         v_team1, v_team2, 1, 0);
                                         
EXCEPTION
WHEN OTHERS THEN
    DBMS_OUTPUT.PUT_LINE('SQLCODE=' || SQLCODE || ' SQLERRM=' || SQLERRM);
    RAISE;
END;
/

SELECT * FROM Match_Game;

EXECUTE DatabaseAdministration.ArchiveWarm;
SELECT * FROM Archived_Warm_Match_Game;


EXECUTE DatabaseAdministration.ArchiveCold;
SELECT * FROM Archived_Cold_Match_Game;

