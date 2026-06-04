
CREATE OR REPLACE TYPE RankedHero AS OBJECT (
    id NUMBER,
    name VARCHAR2(100),
    rate NUMBER
);
/

CREATE OR REPLACE TYPE RankedHeroList AS TABLE OF RankedHero;
/

CREATE OR REPLACE TYPE ChangedHero AS OBJECT (
    id NUMBER,
    name VARCHAR2(100),
    change_flag NUMBER(1), -- 0 = general update, 1 = buff, 2 = nerf, 3 = new
    change_description VARCHAR2(100)
);
/

CREATE OR REPLACE TYPE ChangedHeroList AS TABLE OF ChangedHero;
/


CREATE OR REPLACE TYPE HeroWinRateTrend AS OBJECT ( -- Win rate na przestrzeni pięciu ostatnich patchy
    win_rate1 NUMBER,
    win_rate2 NUMBER,
    win_rate3 NUMBER,
    win_rate4 NUMBER,
    win_rate5 NUMBER
);
/


CREATE OR REPLACE PACKAGE HeroStatistics
AS

    FUNCTION RankByWinRate(v_rank IN Player.rank%TYPE,
                           v_pos IN Hero_Played.position%TYPE,
                           v_attr IN Hero.primary_attribute%TYPE,
                           v_startTime IN DATE, v_endTime IN DATE) RETURN RankedHeroList PIPELINED;
                           
    FUNCTION RankByPickRate(v_rank IN Player.rank%TYPE,
                            v_pos IN Hero_Played.position%TYPE,
                            v_attr IN Hero.primary_attribute%TYPE,
                            v_startTime IN DATE, v_endTime IN DATE) RETURN RankedHeroList PIPELINED;

    
    FUNCTION CalculateHeroWinRate(v_heroId IN Hero.id%TYPE,
                                  v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER;
                                  
    FUNCTION CalculateHeroWinRateExtended(v_heroId IN Hero.id%TYPE,
                                          v_rank IN Player.rank%TYPE,
                                          v_pos IN Hero_Played.position%TYPE,
                                          v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER;
                                  
    FUNCTION CalculateHeroPickRate(v_heroId IN Hero.id%TYPE,
                                   v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER;
                                   
    FUNCTION CalculateHeroPickRateExtended(v_heroId IN Hero.id%TYPE,
                                           v_rank IN Player.rank%TYPE,
                                           v_pos IN Hero_Played.position%TYPE,
                                           v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER;
                                           
                                           
    FUNCTION GetHerosWithFlag(v_patchId IN Patch_Info.id%TYPE,
                              v_flag IN NUMBER) RETURN ChangedHeroList PIPELINED;
             
                              
    FUNCTION CalculateWinRateInPatch(v_heroId IN Hero.id%TYPE,
                                     v_patchId IN Patch_Info.id%TYPE) RETURN NUMBER;           
                              
    FUNCTION CalculateWinRateDelta(v_heroId IN Hero.id%TYPE,
                                   v_patchId IN Patch_Info.id%TYPE) RETURN NUMBER;
                                   
    FUNCTION GetHeroWinRateTrend(v_heroId IN Hero.id%TYPE) RETURN HeroWinRateTrend;
    
END HeroStatistics;
/


CREATE OR REPLACE PACKAGE BODY HeroStatistics
AS

    FUNCTION RankByWinRate(v_rank IN Player.rank%TYPE,
                           v_pos IN Hero_Played.position%TYPE,
                           v_attr IN Hero.primary_attribute%TYPE,
                           v_startTime IN DATE, v_endTime IN DATE) RETURN RankedHeroList PIPELINED
    AS
        CURSOR c_cur IS (SELECT *
                         FROM (SELECT id, name, CalculateHeroWinRateExtended(id, v_rank, v_pos, v_startTime, v_endTime) rate
                               FROM Hero
                               WHERE (upper(primary_attribute) = upper(v_attr) OR v_attr IS NULL)
                               ORDER BY rate DESC));
    BEGIN
        
        FOR v_rec IN c_cur LOOP
            PIPE ROW (RankedHero(v_rec.id, v_rec.name, v_rec.rate));
        END LOOP;
        
    END;
                           
    FUNCTION RankByPickRate(v_rank IN Player.rank%TYPE,
                            v_pos IN Hero_Played.position%TYPE,
                            v_attr IN Hero.primary_attribute%TYPE,
                            v_startTime IN DATE, v_endTime IN DATE) RETURN RankedHeroList PIPELINED
    AS
        CURSOR c_cur IS (SELECT *
                         FROM (SELECT id, name, CalculateHeroPickRateExtended(id, v_rank, v_pos, v_startTime, v_endTime) rate
                               FROM Hero
                               WHERE (upper(primary_attribute) = upper(v_attr) OR v_attr IS NULL)
                               ORDER BY rate DESC));
    BEGIN
        
        FOR v_rec IN c_cur LOOP
            PIPE ROW (RankedHero(v_rec.id, v_rec.name, v_rec.rate));
        END LOOP;
        
    END;
    


    FUNCTION CalculateHeroWinRate(v_heroId IN Hero.id%TYPE,
                                  v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER
    AS
    BEGIN
        RETURN CalculateHeroWinRateExtended(v_heroId, NULL, NULL, v_startTime, v_endTime);
    END;
    
    
    FUNCTION CalculateHeroWinRateExtended(v_heroId IN Hero.id%TYPE,
                                          v_rank IN Player.rank%TYPE,
                                          v_pos IN Hero_Played.position%TYPE,
                                          v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER
    AS
        v_numWon NUMBER := 0;
        v_numTotal NUMBER := 0;
        
        CURSOR c_cur IS (SELECT m.id matchId, t.id teamId, winner_id winnerId
                         FROM Match_Game m, Team t, Hero_Played hp, Player p
                         WHERE match_time >= v_startTime AND match_time <= v_endTime AND
                               (t.id = m.team1_id OR t.id = m.team2_id) AND
                               (hp.id = t.hp1 OR
                                hp.id = t.hp2 OR
                                hp.id = t.hp3 OR
                                hp.id = t.hp4 OR
                                hp.id = t.hp5) AND
                               p.steam_id = hp.steam_id AND
                               hp.hero_id = v_heroId AND
                               (hp.position = v_pos OR v_pos IS NULL) AND
                               (upper(p.rank) = upper(v_rank) OR v_rank IS NULL));
    BEGIN
        
        FOR v_rec IN c_cur LOOP
            v_numTotal := v_numTotal + 1;
            IF v_rec.teamId = v_rec.winnerId THEN
                v_numWon := v_numWon + 1;
            END IF;
        END LOOP;
        
        RETURN v_numWon * 100 / v_numTotal;
    EXCEPTION
    WHEN ZERO_DIVIDE THEN
        RETURN 0;
    END;
    
    
    FUNCTION CalculateHeroPickRate(v_heroId IN Hero.id%TYPE,
                                   v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER
    AS
    BEGIN
        RETURN CalculateHeroPickRateExtended(v_heroId, NULL, NULL, v_startTime, v_endTime);
    END;
    
    
    FUNCTION CalculateHeroPickRateExtended(v_heroId IN Hero.id%TYPE,
                                           v_rank IN Player.rank%TYPE,
                                           v_pos IN Hero_Played.position%TYPE,
                                           v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER
    AS
        v_numPicked NUMBER := 0;
        v_numTotal NUMBER := 0;
        
        v_numHeroInMatch NUMBER := 0;
        
        CURSOR c_cur IS (SELECT id match_id, team1_id, team2_id
                         FROM Match_Game
                         WHERE match_time >= v_startTime AND match_time <= v_endTime);
    BEGIN
    
        FOR v_rec IN c_cur LOOP
            
            SELECT count(1) INTO v_numHeroInMatch
            FROM Team t, Hero_Played hp, Player p
            WHERE (t.id = v_rec.team1_id OR t.id = v_rec.team2_id) AND
                  (hp.id = t.hp1 OR
                   hp.id = t.hp2 OR
                   hp.id = t.hp3 OR
                   hp.id = t.hp4 OR
                   hp.id = t.hp5) AND
                   hp.hero_id = v_heroId AND
                   p.steam_id = hp.steam_id AND
                   (hp.position = v_pos OR v_pos IS NULL) AND
                   (upper(p.rank) = upper(v_rank) OR v_rank IS NULL);
                   
            v_numTotal := v_numTotal + 1;
            
            IF v_numHeroInMatch >= 1 THEN
                v_numPicked := v_numPicked + 1;
            END IF;
            
        END LOOP;
    
        RETURN v_numPicked * 100 / v_numTotal;
    EXCEPTION
    WHEN ZERO_DIVIDE THEN
        RETURN 0;
    END;
    
    
    FUNCTION GetHerosWithFlag(v_patchId IN Patch_Info.id%TYPE,
                              v_flag IN NUMBER) RETURN ChangedHeroList PIPELINED
    AS
    BEGIN
        FOR v_rec IN (SELECT h.id heroId, name, change_flag, change_description 
                      FROM Hero h JOIN Patch_Hero_Change p
                          ON h.id = p.hero_id
                      WHERE p.patch_id = v_patchId AND p.change_flag = v_flag)
        LOOP
            PIPE ROW (ChangedHero(v_rec.heroId, v_rec.name, v_rec.change_flag, v_rec.change_description));
        END LOOP;
    END;
    
    
    
    FUNCTION CalculateWinRateInPatch(v_heroId IN Hero.id%TYPE,
                                     v_patchId IN Patch_Info.id%TYPE) RETURN NUMBER
    AS
        v_patchDate DATE;
        v_nextPatchDate DATE;
        
        v_hasNextDate NUMBER;
        
        v_winRate NUMBER;
    BEGIN
        SELECT release_date INTO v_patchDate
        FROM Patch_Info
        WHERE id = v_patchId;
                              
        SELECT count(id) INTO v_hasNextDate
        FROM Patch_Info
        WHERE release_date > v_patchDate;
        
        IF v_hasNextDate >= 1 THEN
            SELECT release_date INTO v_nextPatchDate
            FROM Patch_Info
            WHERE release_date = (SELECT min(release_date)
                                  FROM Patch_Info
                                  WHERE release_date > v_patchDate);
        ELSE
            v_nextPatchDate := TO_DATE('2100-01-01'); -- Maksymalna założona data, do której będą wstawiane nowe rozgrywki
        END IF;
        
        
        v_winRate := CalculateHeroWinRate(v_heroId, v_patchDate, v_nextPatchDate - 1); -- Od analizowanego patcha do dnia poprzedzającego wydanie nowego
        
        RETURN v_winRate;
    END;
    
    
    FUNCTION CalculateWinRateDelta(v_heroId IN Hero.id%TYPE,
                                   v_patchId IN Patch_Info.id%TYPE) RETURN NUMBER
    AS
        v_prevPatchDate DATE;
        v_patchDate DATE;
        v_nextPatchDate DATE;
        
        v_hasPrevDate NUMBER;
        v_hasNextDate NUMBER;
        
        v_beforePatchWinRate NUMBER;
        v_afterPatchWinRate NUMBER;
    BEGIN
    
        SELECT release_date INTO v_patchDate
        FROM Patch_Info
        WHERE id = v_patchId;
        
        SELECT count(id) INTO v_hasPrevDate
        FROM Patch_Info
        WHERE release_date < v_patchDate;
                              
        SELECT count(id) INTO v_hasNextDate
        FROM Patch_Info
        WHERE release_date > v_patchDate;
        
        
        IF v_hasPrevDate >= 1 THEN
            SELECT release_date INTO v_prevPatchDate 
            FROM Patch_Info
            WHERE release_date = (SELECT max(release_date)
                                  FROM Patch_Info
                                  WHERE release_date < v_patchDate);
        ELSE
            v_prevPatchDate := TO_DATE('2000-01-01'); -- Minimalna data, od której będą analizowane rozgrywki
        END IF;
        
        IF v_hasNextDate >= 1 THEN
            SELECT release_date INTO v_nextPatchDate
            FROM Patch_Info
            WHERE release_date = (SELECT min(release_date)
                                  FROM Patch_Info
                                  WHERE release_date > v_patchDate);
        ELSE
            v_nextPatchDate := TO_DATE('2100-01-01'); -- Maksymalna założona data, do której będą wstawiane nowe rozgrywki
        END IF;
        
        
        v_beforePatchWinRate := CalculateHeroWinRate(v_heroId, v_prevPatchDate, v_patchDate - 1); -- Od ostatniego patcha do dnia poprzedzającego interesujący nas patch
        
        v_afterPatchWinRate := CalculateHeroWinRate(v_heroId, v_patchDate, v_nextPatchDate - 1); -- Od analizowanego patcha do dnia poprzedzającego wydanie nowego
        
        RETURN v_afterPatchWinRate - v_beforePatchWinRate;
    END;
    
    
    FUNCTION GetHeroWinRateTrend(v_heroId IN Hero.id%TYPE) RETURN HeroWinRateTrend
    AS
        v_trend HeroWinRateTrend := HeroWinRateTrend(0, 0, 0, 0, 0);
        v_counter NUMBER := 5;
        v_winRate NUMBER := 0;
        
        CURSOR c_cur IS (SELECT id patchId
                         FROM Patch_Info
                         ORDER BY release_date DESC
                         FETCH FIRST 5 ROWS ONLY);
    BEGIN
    
        FOR v_rec IN c_cur LOOP
            v_winRate := CalculateWinRateInPatch(v_heroId, v_rec.patchId);
            
            CASE v_counter
            WHEN 1 THEN
                v_trend.win_rate1 := v_winRate;
            WHEN 2 THEN
                v_trend.win_rate2 := v_winRate;
            WHEN 3 THEN
                v_trend.win_rate3 := v_winRate;
            WHEN 4 THEN
                v_trend.win_rate4 := v_winRate;
            WHEN 5 THEN
                v_trend.win_rate5 := v_winRate;
            END CASE;
            
            v_counter := v_counter - 1;
        END LOOP;
        
        RETURN v_trend;
    END;
    
END HeroStatistics;
/


COMMIT;


SELECT * FROM table(HeroStatistics.RankByWinRate('Immortal', NULL, 'Agility', TO_DATE('2005-05-05'), TO_DATE('2027-05-05')));

SELECT * FROM table(HeroStatistics.RankByPickRate('Divine', NULL, 'Intelligence', TO_DATE('2005-05-05'), TO_DATE('2027-05-05')));



SELECT id, name, HeroStatistics.CalculateHeroWinRate(id, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) WinRate
FROM Hero;

SELECT id, name, HeroStatistics.CalculateHeroWinRateExtended(id, 'Immortal', 1, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) WinRate
FROM Hero;


SELECT id, name, HeroStatistics.CalculateHeroPickRate(id, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) WinRate
FROM Hero;

SELECT id, name, HeroStatistics.CalculateHeroPickRate(id, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) WinRate
FROM Hero;

SELECT id, name, HeroStatistics.CalculateHeroPickRateExtended(id, 'Divine', 1, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) WinRate
FROM Hero;

SELECT * FROM table(HeroStatistics.GetHerosWithFlag((SELECT id FROM Patch_Info WHERE version_string='7.41c'), 1));


SELECT target_name, HeroStatistics.CalculateWinRateInPatch(target_id, (SELECT id FROM Patch_Info WHERE version_string='7.41a'))
FROM table(GeneralInfo.GetPatchHeroChanges((SELECT id FROM Patch_Info WHERE version_string='7.41a')));


SELECT target_name, HeroStatistics.CalculateWinRateDelta(target_id, (SELECT id FROM Patch_Info WHERE version_string='7.41a'))
FROM table(GeneralInfo.GetPatchHeroChanges((SELECT id FROM Patch_Info WHERE version_string='7.41a')));

SELECT target_name, HeroStatistics.CalculateWinRateDelta(target_id, (SELECT id FROM Patch_Info WHERE version_string='7.41b'))
FROM table(GeneralInfo.GetPatchHeroChanges((SELECT id FROM Patch_Info WHERE version_string='7.41b')));

SELECT target_name, HeroStatistics.CalculateWinRateDelta(target_id, (SELECT id FROM Patch_Info WHERE version_string='7.41c'))
FROM table(GeneralInfo.GetPatchHeroChanges((SELECT id FROM Patch_Info WHERE version_string='7.41c')));


SET SERVEROUTPUT ON;
DECLARE
    v_heroId Hero.id%TYPE;
    v_trend HeroWinRateTrend;
BEGIN
    SELECT id INTO v_heroId FROM Hero WHERE name='Lina';

    v_trend := HeroStatistics.GetHeroWinRateTrend(v_heroId);
    
    DBMS_OUTPUT.PUT_LINE(v_trend.win_rate1 || ' ' || v_trend.win_rate2 || ' ' || v_trend.win_rate3 || ' ' || v_trend.win_rate4 || ' ' || v_trend.win_rate5);
    
END;
/

