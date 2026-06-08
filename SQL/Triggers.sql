
CREATE OR REPLACE TRIGGER OnHeroUpdateTrigger
AFTER UPDATE ON Hero
FOR EACH ROW
DECLARE
    v_hasPatch NUMBER;
    v_patchId Patch_Info.id%TYPE;
BEGIN

    SELECT count(id) INTO v_hasPatch
    FROM Patch_Info;
    IF v_hasPatch > 0 THEN
        
        SELECT id INTO v_patchId
        FROM Patch_Info
        WHERE release_date = (SELECT max(release_date) FROM Patch_Info)
        FETCH FIRST 1 ROWS ONLY;
        
        IF :new.name != :old.name THEN
            INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES (v_patchId, :new.id, 0, 'Changed hero''s name from ' || :old.name || ' to ' || :new.name);
        END IF;
        
        IF :new.primary_attribute != :old.primary_attribute THEN
            INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES (v_patchId, :new.id, 0, 'Changed ' || :new.name || '''s primary attribute to ' || :new.primary_attribute);
        END IF;
        
    END IF;
END;
/


CREATE OR REPLACE TRIGGER OnItemUpdateTrigger
AFTER UPDATE ON Item
FOR EACH ROW
DECLARE
    v_hasPatch NUMBER;
    v_patchId Patch_Info.id%TYPE;
BEGIN

    SELECT count(id) INTO v_hasPatch
    FROM Patch_Info;
    IF v_hasPatch > 0 THEN
        
        SELECT id INTO v_patchId
        FROM Patch_Info
        WHERE release_date = (SELECT max(release_date) FROM Patch_Info)
        FETCH FIRST 1 ROWS ONLY;
        
        IF :new.name != :old.name THEN
            INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES (v_patchId, :new.id, 0, 'Changed item''s name from ' || :old.name || ' to ' || :new.name);
        END IF;
        
    END IF;
END;
/


CREATE OR REPLACE TRIGGER OnHeroInsertTrigger
AFTER INSERT ON Hero
FOR EACH ROW
DECLARE
    v_hasPatch NUMBER;
    v_patchId Patch_Info.id%TYPE;
BEGIN
    
    SELECT count(id) INTO v_hasPatch
    FROM Patch_Info;
    IF v_hasPatch > 0 THEN
        
        SELECT id INTO v_patchId
        FROM Patch_Info
        WHERE release_date = (SELECT max(release_date) FROM Patch_Info)
        FETCH FIRST 1 ROWS ONLY;
        
        INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES (v_patchId, :new.id, 3, 'Added new hero: ' || :new.name);
        
    END IF;
    
END;
/


CREATE OR REPLACE TRIGGER OnItemInsertTrigger
AFTER INSERT ON Item
FOR EACH ROW
DECLARE
    v_hasPatch NUMBER;
    v_patchId Patch_Info.id%TYPE;
BEGIN
    
    SELECT count(id) INTO v_hasPatch
    FROM Patch_Info;
    IF v_hasPatch > 0 THEN
        
        SELECT id INTO v_patchId
        FROM Patch_Info
        WHERE release_date = (SELECT max(release_date) FROM Patch_Info)
        FETCH FIRST 1 ROWS ONLY;
        
        INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES (v_patchId, :new.id, 3, 'Added new item: ' || :new.name);
        
    END IF;
    
END;
/


COMMIT;
