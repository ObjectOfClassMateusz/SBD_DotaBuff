
BEGIN
    FOR t IN (SELECT table_name FROM user_tables
              WHERE table_name IN ('HERO_CHANGE', 'ITEM_CHANGE', 'PATCH_INFO', 'PATCH_HERO_CHANGE', 'PATCH_ITEM_CHANGE'))
    LOOP
        EXECUTE IMMEDIATE 'DROP TABLE ' || t.table_name || ' CASCADE CONSTRAINTS';
    END LOOP;
END;
/

BEGIN
    FOR s IN (SELECT sequence_name FROM user_sequences
              WHERE sequence_name IN ('SEQ_HERO_CHANGE_ID', 'SEQ_ITEM_CHANGE_ID', 'SEQ_PATCH_ID'))
    LOOP
        EXECUTE IMMEDIATE 'DROP SEQUENCE ' || s.sequence_name;
    END LOOP;
END;
/


CREATE SEQUENCE seq_Hero_Change_id START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_Item_Change_id START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_Patch_id START WITH 1 INCREMENT BY 1 NOCACHE;


CREATE TABLE Hero_Change (
    id NUMBER DEFAULT seq_Hero_Change_id.NEXTVAL PRIMARY KEY,
    change_type NUMBER(1),
    change_description VARCHAR2(100),
    CONSTRAINT hero_change_type_check CHECK (change_type IN (0, 1, 2))
);

COMMENT ON COLUMN Hero_Change.change_type  IS '0 = general update, 1 = buff, 2 = nerf, 3 = new';


CREATE TABLE Item_Change (
    id NUMBER DEFAULT seq_Item_Change_id.NEXTVAL PRIMARY KEY,
    change_type NUMBER(1),
    change_description VARCHAR2(100),
    CONSTRAINT item_change_type_check CHECK (change_type IN (0, 1, 2))
);

COMMENT ON COLUMN Item_Change.change_type  IS '0 = general update, 1 = buff, 2 = nerf, 3 = new';



CREATE TABLE Patch_Info (
    id NUMBER DEFAULT seq_Patch_id.NEXTVAL PRIMARY KEY,
    version_string VARCHAR2(30) NOT NULL,
    release_date DATE DEFAULT SYSDATE
);


CREATE TABLE Patch_Hero_Change (
    patch_id NUMBER REFERENCES Patch_Info (id),
    hero_change_id NUMBER REFERENCES Hero_Change (id),
    CONSTRAINT patch_hero_change_pkey PRIMARY KEY (patch_id, hero_change_id)
);


CREATE TABLE Patch_Item_Change (
    patch_id NUMBER REFERENCES Patch_Info (id),
    item_change_id NUMBER REFERENCES Item_Change (id),
    CONSTRAINT patch_item_change_pkey PRIMARY KEY (patch_id, item_change_id)
);

COMMIT;


