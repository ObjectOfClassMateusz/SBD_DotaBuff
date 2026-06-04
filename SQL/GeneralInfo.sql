
CREATE OR REPLACE TYPE ItemPopularity AS OBJECT (
    id NUMBER,
    name VARCHAR2(100),
    popularityRate NUMBER
);
/

CREATE OR REPLACE TYPE ItemPopularityList AS TABLE OF ItemPopularity;
/


CREATE OR REPLACE TYPE PatchChangeDTO AS OBJECT (
    version_string VARCHAR2(30),
    target_id NUMBER,
    target_name VARCHAR(100),
    change_flag NUMBER(1),
    change_description VARCHAR2(100)
);
/

CREATE OR REPLACE TYPE PatchChangeList AS TABLE OF PatchChangeDTO;
/


CREATE OR REPLACE PACKAGE GeneralInfo
AS

    FUNCTION GetLatestPatchId RETURN NUMBER;
    FUNCTION GetPatchHeroChanges(v_patchId Patch_Info.id%TYPE) RETURN PatchChangeList PIPELINED;
    FUNCTION GetPatchItemChanges(v_patchId Patch_Info.id%TYPE) RETURN PatchChangeList PIPELINED;
    FUNCTION GetPatchBalance(v_patchId Patch_Info.id%TYPE) RETURN NUMBER;

    FUNCTION GetNumMatches(v_date IN DATE) RETURN NUMBER;
    
    FUNCTION AvgKDA(v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER;
    
    FUNCTION GetItemPopularity(v_itemId IN Item.id%TYPE, v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER;
    
    FUNCTION RankItemsByPopularity(v_startTime IN DATE, v_endTime IN DATE) RETURN ItemPopularityList PIPELINED;

END GeneralInfo;
/


CREATE OR REPLACE PACKAGE BODY GeneralInfo
AS

    FUNCTION GetLatestPatchId RETURN NUMBER
    AS
        v_id NUMBER := 0;
    BEGIN
        SELECT id INTO v_id
        FROM Patch_Info
        WHERE release_date = (SELECT max(release_date)
                              FROM Patch_Info)
        FETCH FIRST 1 ROWS ONLY;
    
        RETURN v_id;
    END;
    
    FUNCTION GetPatchHeroChanges(v_patchId Patch_Info.id%TYPE) RETURN PatchChangeList PIPELINED
    AS
    BEGIN
        FOR v_rec IN (SELECT version_string, h.id heroId, name, change_flag, change_description 
                      FROM Hero h JOIN Patch_Hero_Change p
                          ON h.id = p.hero_id JOIN Patch_Info pi
                          ON pi.id = p.patch_id
                      WHERE p.patch_id = v_patchId)
        LOOP
            PIPE ROW (PatchChangeDTO(v_rec.version_string, v_rec.heroId, v_rec.name, v_rec.change_flag, v_rec.change_description));
        END LOOP;
    END;
    
    FUNCTION GetPatchItemChanges(v_patchId Patch_Info.id%TYPE) RETURN PatchChangeList PIPELINED
    AS
    BEGIN
        FOR v_rec IN (SELECT version_string, i.id itemId, name, change_flag, change_description 
                      FROM Item i JOIN Patch_Item_Change p
                          ON i.id = p.item_id JOIN Patch_Info pi
                          ON pi.id = p.patch_id
                      WHERE p.patch_id = v_patchId)
        LOOP
            PIPE ROW (PatchChangeDTO(v_rec.version_string, v_rec.itemId, v_rec.name, v_rec.change_flag, v_rec.change_description));
        END LOOP;
    END;


    FUNCTION GetNumMatches(v_date IN DATE) RETURN NUMBER
    AS
        v_count NUMBER := 0;
    BEGIN
        
        SELECT count(id) INTO v_count
        FROM Match_Game
        WHERE TRUNC(match_time) = v_date;
        
        RETURN v_count;
    END;
    
    FUNCTION AvgKDA(v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER
    AS
        v_avgkda NUMBER := 0;
    BEGIN
        SELECT avg(kda) INTO v_avgkda
        FROM Match_Game m, Team t, Hero_Played h
        WHERE match_time >= v_startTime AND match_time <= v_endTime AND
              (t.id = m.team1_id OR t.id = m.team2_id) AND
              (h.id = t.hp1 OR
               h.id = t.hp2 OR
               h.id = t.hp3 OR
               h.id = t.hp4 OR
               h.id = t.hp5);
    
        RETURN v_avgkda;
    END;
    
    FUNCTION GetItemPopularity(v_itemId IN Item.id%TYPE, v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER
    AS
        v_numTotalPlayerMatches NUMBER := 0;
        v_numItemUsed NUMBER := 0;
        
        v_itemUsedByPlayer NUMBER := 0;
        
        CURSOR c_cur IS (SELECT hp.id heroPlayedId
                         FROM Match_Game m, Team t, Hero_Played hp
                         WHERE match_time >= v_startTime AND match_time <= v_endTime AND
                               (t.id = m.team1_id OR t.id = m.team2_id) AND
                               (hp.id = t.hp1 OR
                                hp.id = t.hp2 OR
                                hp.id = t.hp3 OR
                                hp.id = t.hp4 OR
                                hp.id = t.hp5));
    BEGIN
        
        FOR v_rec IN c_cur LOOP
            
            SELECT count(i.id) INTO v_itemUsedByPlayer
            FROM Item i, Hero_Played hp
            WHERE i.id = v_itemId AND
                  hp.id = v_rec.heroPlayedId AND
                  (hp.slot1 = i.id OR
                   hp.slot2 = i.id OR
                   hp.slot3 = i.id OR
                   hp.slot4 = i.id OR
                   hp.slot5 = i.id OR
                   hp.slot6 = i.id);
            
            v_numTotalPlayerMatches := v_numTotalPlayerMatches + 1;
            
            IF v_itemUsedByPlayer >= 1 THEN
                v_numItemUsed := v_numItemUsed + 1;
            END IF;
        END LOOP;
        
        RETURN v_numItemUsed * 100 / v_numTotalPlayerMatches;
    
    EXCEPTION
    WHEN ZERO_DIVIDE THEN
        RETURN 0;
    END;
    
    
    FUNCTION RankItemsByPopularity(v_startTime IN DATE, v_endTime IN DATE) RETURN ItemPopularityList PIPELINED
    AS
        CURSOR c_cur IS (SELECT *
                         FROM (SELECT id, name, GeneralInfo.GetItemPopularity(id, v_startTime, v_endTime) popularity
                               FROM Item
                               ORDER BY popularity DESC));
    BEGIN
        
        FOR v_rec IN c_cur LOOP
            PIPE ROW (ItemPopularity(v_rec.id, v_rec.name, v_rec.popularity));
        END LOOP;
        
    END;
    
    
    FUNCTION GetPatchBalance(v_patchId Patch_Info.id%TYPE) RETURN NUMBER
    AS
        v_numBuffsHeros NUMBER := 0;
        v_numNerfsHeros NUMBER := 0;
        
        v_numBuffsItems NUMBER := 0;
        v_numNerfsItems NUMBER := 0;
    BEGIN
        
        SELECT count(id) INTO v_numBuffsHeros
        FROM Patch_Hero_Change
        WHERE patch_id = v_patchId AND change_flag = 1;
        
        SELECT count(id) INTO v_numNerfsHeros
        FROM Patch_Hero_Change
        WHERE patch_id = v_patchId AND change_flag = 2;
        
        SELECT count(id) INTO v_numBuffsItems
        FROM Patch_Item_Change
        WHERE patch_id = v_patchId AND change_flag = 1;
        
        SELECT count(id) INTO v_numNerfsItems
        FROM Patch_Item_Change
        WHERE patch_id = v_patchId AND change_flag = 2;
        
        RETURN v_numBuffsHeros - v_numNerfsHeros + v_numBuffsItems - v_numNerfsItems;
    END;
    
    
END GeneralInfo;
/


COMMIT;


SELECT GeneralInfo.GetLatestPatchId FROM DUAL;
SELECT * FROM table(GeneralInfo.GetPatchHeroChanges(GeneralInfo.GetLatestPatchId));
SELECT * FROM table(GeneralInfo.GetPatchItemChanges(GeneralInfo.GetLatestPatchId));

SELECT version_string, GeneralInfo.GetPatchBalance(id) FROM Patch_Info;

SELECT GeneralInfo.GetNumMatches(TO_DATE('24/11/15')) FROM DUAL;


SELECT GeneralInfo.AvgKDA(TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) FROM DUAL;

SELECT GeneralInfo.GetItemPopularity(id, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) FROM Item;

SELECT * FROM table(GeneralInfo.RankItemsByPopularity(TO_DATE('2005-05-05'), TO_DATE('2027-05-05')));



