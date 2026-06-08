
BEGIN
    FOR t IN (SELECT table_name FROM user_tables
              WHERE table_name IN ('PATCH_INFO', 'PATCH_HERO_CHANGE', 'PATCH_ITEM_CHANGE'))
    LOOP
        EXECUTE IMMEDIATE 'DROP TABLE ' || t.table_name || ' CASCADE CONSTRAINTS';
    END LOOP;
END;
/

BEGIN
    FOR s IN (SELECT sequence_name FROM user_sequences
              WHERE sequence_name IN ('SEQ_PATCH_HERO_CHANGE_ID', 'SEQ_PTACH_ITEM_CHANGE_ID', 'SEQ_PATCH_ID'))
    LOOP
        EXECUTE IMMEDIATE 'DROP SEQUENCE ' || s.sequence_name;
    END LOOP;
END;
/


CREATE SEQUENCE seq_Patch_Hero_Change_id START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_Patch_Item_Change_id START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_Patch_id START WITH 1 INCREMENT BY 1 NOCACHE;



CREATE TABLE Patch_Info (
    id NUMBER DEFAULT seq_Patch_id.NEXTVAL PRIMARY KEY,
    version_string VARCHAR2(30) NOT NULL,
    release_date DATE DEFAULT SYSDATE
);


CREATE TABLE Patch_Hero_Change (
    id NUMBER DEFAULT seq_Patch_Hero_Change_id.NEXTVAL PRIMARY KEY,
    patch_id NUMBER REFERENCES Patch_Info (id),
    hero_id NUMBER REFERENCES Hero (id),
    
    change_flag NUMBER(1),
    change_description VARCHAR2(100),
    CONSTRAINT hero_change_flag_check CHECK (change_flag IN (0, 1, 2, 3))
);

COMMENT ON COLUMN Patch_Hero_Change.change_flag  IS '0 = general update, 1 = buff, 2 = nerf, 3 = new';


CREATE TABLE Patch_Item_Change (
    id NUMBER DEFAULT seq_Patch_Item_Change_id.NEXTVAL PRIMARY KEY,
    patch_id NUMBER REFERENCES Patch_Info (id),
    item_id NUMBER REFERENCES Item (id),
    
    change_flag NUMBER(1),
    change_description VARCHAR2(100),
    CONSTRAINT item_change_flag_check CHECK (change_flag IN (0, 1, 2, 3))
);

COMMENT ON COLUMN Patch_Item_Change.change_flag  IS '0 = general update, 1 = buff, 2 = nerf, 3 = new';



INSERT INTO Patch_Info (version_string, release_date) VALUES ('7.41a', TO_DATE('2018-10-18'));
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41a'), (SELECT id FROM Hero WHERE name='Bane'), 1, 'Mana cost decreased');
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41a'), (SELECT id FROM Hero WHERE name='Doom'), 2, 'Bonus HP decreased');
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41a'), (SELECT id FROM Hero WHERE name='Tinker'), 0, 'Sound effect change');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41a'), (SELECT id FROM Item WHERE name='Bloodstone'), 1, 'Parameters increased');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41a'), (SELECT id FROM Item WHERE name='Soul Ring'), 1, 'Cooldown time increased');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41a'), (SELECT id FROM Item WHERE name='Boots of Travel'), 2, 'Speed decreased');


INSERT INTO Patch_Info (version_string, release_date) VALUES ('7.41b', TO_DATE('2024-11-18'));
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41b'), (SELECT id FROM Hero WHERE name='Enigma'), 2, 'Mana cost increased');
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41b'), (SELECT id FROM Hero WHERE name='Zeus'), 0, 'Looks changed');
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41b'), (SELECT id FROM Hero WHERE name='Bane'), 1, 'Mana cost decreased');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41b'), (SELECT id FROM Item WHERE name='Urn of Shadows'), 1, 'Parameters increased');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41b'), (SELECT id FROM Item WHERE name='Mekansm'), 2, 'Cooldown time increased');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41b'), (SELECT id FROM Item WHERE name='Arcane Blink'), 2, 'Speed decreased');


INSERT INTO Patch_Info (version_string, release_date) VALUES ('7.41c', TO_DATE('2026-06-02'));
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41c'), (SELECT id FROM Hero WHERE name='Muerta'), 1, 'Speed increased');
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41c'), (SELECT id FROM Hero WHERE name='Lion'), 2, 'HP decreased');
INSERT INTO Patch_Hero_Change (patch_id, hero_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41c'), (SELECT id FROM Hero WHERE name='Dazzle'), 1, 'Mana cost decreased');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41c'), (SELECT id FROM Item WHERE name='Shadow Amulet'), 1, 'Parameters increased');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41c'), (SELECT id FROM Item WHERE name='Sange'), 2, 'Cooldown time increased');
INSERT INTO Patch_Item_Change (patch_id, item_id, change_flag, change_description) VALUES ((SELECT id FROM Patch_Info WHERE version_string='7.41c'), (SELECT id FROM Item WHERE name='Radiance'), 0, 'Icon change');





COMMIT;


