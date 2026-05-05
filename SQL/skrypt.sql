-- ============================================================
--  DOTA 2 ANALYSIS APP  |  Oracle Database  |  FIXED VERSION
-- ============================================================
--  Root cause of ORA-00001:
--  Oracle's INSERT ALL calls NEXTVAL only ONCE per row of the
--  driving SELECT, so all INTO clauses in one INSERT ALL block
--  get the SAME sequence value → PK collision.
--  Fix: use individual INSERT statements for sequenced tables.
-- ============================================================

-- ────────────────────────────────────────────────────────────
--  DROP TABLES (safe re-run)
-- ────────────────────────────────────────────────────────────
BEGIN
    FOR t IN (SELECT table_name FROM user_tables
              WHERE table_name IN ('PLAYER','HERO','ITEM'))
    LOOP
        EXECUTE IMMEDIATE 'DROP TABLE ' || t.table_name || ' CASCADE CONSTRAINTS';
    END LOOP;
END;
/

BEGIN
    FOR s IN (SELECT sequence_name FROM user_sequences
              WHERE sequence_name IN ('SEQ_ITEM_ID','SEQ_HERO_ID'))
    LOOP
        EXECUTE IMMEDIATE 'DROP SEQUENCE ' || s.sequence_name;
    END LOOP;
END;
/

-- ────────────────────────────────────────────────────────────
--  SEQUENCES
-- ────────────────────────────────────────────────────────────
CREATE SEQUENCE seq_item_id START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE seq_hero_id START WITH 1 INCREMENT BY 1 NOCACHE;

-- ────────────────────────────────────────────────────────────
--  TABLE: ITEM
-- ────────────────────────────────────────────────────────────
CREATE TABLE Item (
    id    NUMBER        DEFAULT seq_item_id.NEXTVAL CONSTRAINT pk_item PRIMARY KEY,
    name  VARCHAR2(100) CONSTRAINT nn_item_name NOT NULL,
                        CONSTRAINT uq_item_name UNIQUE (name)
);

-- ────────────────────────────────────────────────────────────
--  TABLE: HERO
-- ────────────────────────────────────────────────────────────
CREATE TABLE Hero (
    id                NUMBER        DEFAULT seq_hero_id.NEXTVAL CONSTRAINT pk_hero PRIMARY KEY,
    name              VARCHAR2(100) CONSTRAINT nn_hero_name NOT NULL,
                                    CONSTRAINT uq_hero_name UNIQUE (name),
    primary_attribute VARCHAR2(15)  CONSTRAINT nn_hero_attr NOT NULL,
                                    CONSTRAINT ck_hero_attr CHECK (
                                        primary_attribute IN ('Strength','Agility','Intelligence','Universal')
                                    )
);

-- ────────────────────────────────────────────────────────────
--  TABLE: PLAYER
-- ────────────────────────────────────────────────────────────
CREATE TABLE Player (
    steam_id        NUMBER(17)   CONSTRAINT pk_player PRIMARY KEY,
    nickname        VARCHAR2(64) CONSTRAINT nn_player_nick NOT NULL,
    region          VARCHAR2(64) CONSTRAINT nn_player_region NOT NULL,
                                 CONSTRAINT ck_player_region CHECK (
                                    region IN ('CHINA','RUSSIA','N-AMERICA','S-AMERICA','W-EUROPE','E-EUROPE','FILIPINO')
                                    ),
    account_created DATE         CONSTRAINT nn_player_date NOT NULL,
    rank            VARCHAR2(20) CONSTRAINT nn_player_rank NOT NULL,
                                 CONSTRAINT ck_player_rank CHECK (
                                     rank IN ('Herald','Guardian','Crusader','Archon',
                                              'Legend','Ancient','Divine','Immortal')
                                 )
);


-- ============================================================
--  ITEMS  (individual INSERTs — avoids sequence collision)
-- ============================================================
-- Starting / Consumables
INSERT INTO Item (name) VALUES ('Tango');
INSERT INTO Item (name) VALUES ('Healing Salve');
INSERT INTO Item (name) VALUES ('Clarity');
INSERT INTO Item (name) VALUES ('Faerie Fire');
INSERT INTO Item (name) VALUES ('Smoke of Deceit');
INSERT INTO Item (name) VALUES ('Town Portal Scroll');
-- Basic stat components
INSERT INTO Item (name) VALUES ('Iron Branch');
INSERT INTO Item (name) VALUES ('Gauntlets of Strength');
INSERT INTO Item (name) VALUES ('Slippers of Agility');
INSERT INTO Item (name) VALUES ('Mantle of Intelligence');
INSERT INTO Item (name) VALUES ('Circlet');
INSERT INTO Item (name) VALUES ('Belt of Strength');
INSERT INTO Item (name) VALUES ('Band of Elvenskin');
INSERT INTO Item (name) VALUES ('Robe of the Magi');
INSERT INTO Item (name) VALUES ('Sage''s Mask');
INSERT INTO Item (name) VALUES ('Orb of Venom');
INSERT INTO Item (name) VALUES ('Quelling Blade');
INSERT INTO Item (name) VALUES ('Stout Shield');
INSERT INTO Item (name) VALUES ('Wind Lace');
-- Attack components
INSERT INTO Item (name) VALUES ('Blades of Attack');
INSERT INTO Item (name) VALUES ('Gloves of Haste');
INSERT INTO Item (name) VALUES ('Chainmail');
INSERT INTO Item (name) VALUES ('Quarterstaff');
INSERT INTO Item (name) VALUES ('Broadsword');
INSERT INTO Item (name) VALUES ('Claymore');
INSERT INTO Item (name) VALUES ('Mithril Hammer');
INSERT INTO Item (name) VALUES ('Javelin');
-- Armor / HP components
INSERT INTO Item (name) VALUES ('Helm of Iron Will');
INSERT INTO Item (name) VALUES ('Platemail');
INSERT INTO Item (name) VALUES ('Vitality Booster');
INSERT INTO Item (name) VALUES ('Energy Booster');
INSERT INTO Item (name) VALUES ('Point Booster');
-- Attribute components
INSERT INTO Item (name) VALUES ('Ogre Axe');
INSERT INTO Item (name) VALUES ('Blade of Alacrity');
INSERT INTO Item (name) VALUES ('Staff of Wizardry');
INSERT INTO Item (name) VALUES ('Ultimate Orb');
INSERT INTO Item (name) VALUES ('Demon Edge');
INSERT INTO Item (name) VALUES ('Sacred Relic');
INSERT INTO Item (name) VALUES ('Mystic Staff');
INSERT INTO Item (name) VALUES ('Reaver');
INSERT INTO Item (name) VALUES ('Hyperstone');
-- Regen components
INSERT INTO Item (name) VALUES ('Ring of Health');
INSERT INTO Item (name) VALUES ('Void Stone');
INSERT INTO Item (name) VALUES ('Ring of Regen');
INSERT INTO Item (name) VALUES ('Cloak');
INSERT INTO Item (name) VALUES ('Ghost Scepter');
INSERT INTO Item (name) VALUES ('Blink Dagger');
INSERT INTO Item (name) VALUES ('Shadow Amulet');
-- Boots
INSERT INTO Item (name) VALUES ('Power Treads');
INSERT INTO Item (name) VALUES ('Phase Boots');
INSERT INTO Item (name) VALUES ('Arcane Boots');
INSERT INTO Item (name) VALUES ('Tranquil Boots');
INSERT INTO Item (name) VALUES ('Boots of Travel');
-- Early game upgrades
INSERT INTO Item (name) VALUES ('Wraith Band');
INSERT INTO Item (name) VALUES ('Null Talisman');
INSERT INTO Item (name) VALUES ('Bracer');
INSERT INTO Item (name) VALUES ('Magic Wand');
INSERT INTO Item (name) VALUES ('Soul Ring');
-- Support / Aura items
INSERT INTO Item (name) VALUES ('Urn of Shadows');
INSERT INTO Item (name) VALUES ('Drum of Endurance');
INSERT INTO Item (name) VALUES ('Ancient Janggo');
INSERT INTO Item (name) VALUES ('Vladmir''s Offering');
INSERT INTO Item (name) VALUES ('Pipe of Insight');
INSERT INTO Item (name) VALUES ('Crimson Guard');
INSERT INTO Item (name) VALUES ('Buckler');
INSERT INTO Item (name) VALUES ('Mekansm');
INSERT INTO Item (name) VALUES ('Guardian Greaves');
INSERT INTO Item (name) VALUES ('Headdress');
INSERT INTO Item (name) VALUES ('Ring of Basilius');
INSERT INTO Item (name) VALUES ('Veil of Discord');
INSERT INTO Item (name) VALUES ('Medallion of Courage');
INSERT INTO Item (name) VALUES ('Solar Crest');
-- Mobility / Utility
INSERT INTO Item (name) VALUES ('Force Staff');
INSERT INTO Item (name) VALUES ('Hurricane Pike');
INSERT INTO Item (name) VALUES ('Glimmer Cape');
INSERT INTO Item (name) VALUES ('Aether Lens');
INSERT INTO Item (name) VALUES ('Rod of Atos');
-- Intelligence / Spell items
INSERT INTO Item (name) VALUES ('Dagon');
INSERT INTO Item (name) VALUES ('Necronomicon');
INSERT INTO Item (name) VALUES ('Orchid Malevolence');
INSERT INTO Item (name) VALUES ('Bloodthorn');
INSERT INTO Item (name) VALUES ('Eul''s Scepter of Divinity');
INSERT INTO Item (name) VALUES ('Scythe of Vyse');
INSERT INTO Item (name) VALUES ('Aghanim''s Scepter');
INSERT INTO Item (name) VALUES ('Aghanim''s Shard');
INSERT INTO Item (name) VALUES ('Refresher Orb');
INSERT INTO Item (name) VALUES ('Octarine Core');
-- Hybrid stat items
INSERT INTO Item (name) VALUES ('Kaya');
INSERT INTO Item (name) VALUES ('Yasha');
INSERT INTO Item (name) VALUES ('Sange');
INSERT INTO Item (name) VALUES ('Sange and Yasha');
INSERT INTO Item (name) VALUES ('Kaya and Sange');
INSERT INTO Item (name) VALUES ('Yasha and Kaya');
INSERT INTO Item (name) VALUES ('Manta Style');
INSERT INTO Item (name) VALUES ('Diffusal Blade');
INSERT INTO Item (name) VALUES ('Eye of Skadi');
-- Attack speed / Crit / Physical
INSERT INTO Item (name) VALUES ('Maelstrom');
INSERT INTO Item (name) VALUES ('Mjollnir');
INSERT INTO Item (name) VALUES ('Monkey King Bar');
INSERT INTO Item (name) VALUES ('Daedalus');
INSERT INTO Item (name) VALUES ('Butterfly');
INSERT INTO Item (name) VALUES ('Dragon Lance');
INSERT INTO Item (name) VALUES ('Silver Edge');
INSERT INTO Item (name) VALUES ('Skull Basher');
INSERT INTO Item (name) VALUES ('Abyssal Blade');
INSERT INTO Item (name) VALUES ('Divine Rapier');
INSERT INTO Item (name) VALUES ('Radiance');
-- Late game power items
INSERT INTO Item (name) VALUES ('Heart of Tarrasque');
INSERT INTO Item (name) VALUES ('Satanic');
INSERT INTO Item (name) VALUES ('Armlet of Mordiggian');
INSERT INTO Item (name) VALUES ('Battle Fury');
INSERT INTO Item (name) VALUES ('Mask of Madness');
INSERT INTO Item (name) VALUES ('Desolator');
INSERT INTO Item (name) VALUES ('Assault Cuirass');
INSERT INTO Item (name) VALUES ('Shiva''s Guard');
INSERT INTO Item (name) VALUES ('Bloodstone');
INSERT INTO Item (name) VALUES ('Linken''s Sphere');
INSERT INTO Item (name) VALUES ('Black King Bar');
INSERT INTO Item (name) VALUES ('Lotus Orb');
INSERT INTO Item (name) VALUES ('Blade Mail');
INSERT INTO Item (name) VALUES ('Hood of Defiance');
INSERT INTO Item (name) VALUES ('Eternal Shroud');
INSERT INTO Item (name) VALUES ('Aeon Disk');
INSERT INTO Item (name) VALUES ('Nullifier');
INSERT INTO Item (name) VALUES ('Ethereal Blade');
INSERT INTO Item (name) VALUES ('Gleipnir');
INSERT INTO Item (name) VALUES ('Parasma');
-- Blink upgrades
INSERT INTO Item (name) VALUES ('Overwhelming Blink');
INSERT INTO Item (name) VALUES ('Swift Blink');
INSERT INTO Item (name) VALUES ('Arcane Blink');


-- ============================================================
--  HEROES  (individual INSERTs — all 4 attribute classes)
-- ============================================================
-- STRENGTH
INSERT INTO Hero (name, primary_attribute) VALUES ('Axe','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Beastmaster','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Brewmaster','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Bristleback','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Centaur Warrunner','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Chaos Knight','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Clockwerk','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Dragon Knight','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Earth Spirit','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Earthshaker','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Elder Titan','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Huskar','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Io','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Kunkka','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Legion Commander','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Lifestealer','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Lycan','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Magnus','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Mars','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Night Stalker','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Omniknight','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Phoenix','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Pudge','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Sand King','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Slardar','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Spirit Breaker','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Sven','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Tidehunter','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Timbersaw','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Tiny','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Treant Protector','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Tusk','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Underlord','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Undying','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Wraith King','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Primal Beast','Strength');
INSERT INTO Hero (name, primary_attribute) VALUES ('Marci','Strength');
-- AGILITY
INSERT INTO Hero (name, primary_attribute) VALUES ('Anti-Mage','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Arc Warden','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Bloodseeker','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Bounty Hunter','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Broodmother','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Clinkz','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Drow Ranger','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Ember Spirit','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Faceless Void','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Gyrocopter','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Juggernaut','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Lone Druid','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Luna','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Medusa','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Meepo','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Mirana','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Monkey King','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Morphling','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Naga Siren','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Pangolier','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Phantom Assassin','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Phantom Lancer','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Razor','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Riki','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Shadow Fiend','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Slark','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Sniper','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Spectre','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Templar Assassin','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Terror Blade','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Troll Warlord','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Ursa','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Vengeful Spirit','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Venomancer','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Viper','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Weaver','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Hoodwink','Agility');
INSERT INTO Hero (name, primary_attribute) VALUES ('Muerta','Agility');
-- INTELLIGENCE
INSERT INTO Hero (name, primary_attribute) VALUES ('Ancient Apparition','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Bane','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Batrider','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Chen','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Crystal Maiden','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Dark Seer','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Dark Willow','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Dazzle','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Death Prophet','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Disruptor','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Enchantress','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Enigma','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Grimstroke','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Invoker','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Jakiro','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Keeper of the Light','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Leshrac','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Lich','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Lina','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Lion','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Nature''s Prophet','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Necrophos','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Ogre Magi','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Oracle','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Outworld Destroyer','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Puck','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Pugna','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Queen of Pain','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Rubick','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Shadow Demon','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Shadow Shaman','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Silencer','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Skywrath Mage','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Storm Spirit','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Tinker','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Visage','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Void Spirit','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Warlock','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Witch Doctor','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Zeus','Intelligence');
INSERT INTO Hero (name, primary_attribute) VALUES ('Techies','Intelligence');
-- UNIVERSAL
INSERT INTO Hero (name, primary_attribute) VALUES ('Doom','Universal');
INSERT INTO Hero (name, primary_attribute) VALUES ('Snapfire','Universal');
INSERT INTO Hero (name, primary_attribute) VALUES ('Dawnbreaker','Universal');
INSERT INTO Hero (name, primary_attribute) VALUES ('Kez','Universal');
INSERT INTO Hero (name, primary_attribute) VALUES ('Ringmaster','Universal');


-- ============================================================
--  PLAYERS  (40 players, 5 per rank bracket)
-- ============================================================
-- Herald
INSERT INTO Player VALUES (76561198000111001,'NightWatchman_H','CHINA',  DATE '2012-03-15','Herald');
INSERT INTO Player VALUES (76561198000111002,'IronGrip42','CHINA',       DATE '2015-07-22','Herald');
INSERT INTO Player VALUES (76561198000111003,'NewbieSlayer99','W-EUROPE',    DATE '2019-11-05','Herald');
INSERT INTO Player VALUES (76561198000111004,'CluelessCarry','CHINA',    DATE '2020-02-14','Herald');
INSERT INTO Player VALUES (76561198000111005,'SupportGod_NOT','W-EUROPE',   DATE '2021-06-30','Herald');
-- Guardian
INSERT INTO Player VALUES (76561198000222001,'GankOrFeed',  'W-EUROPE',     DATE '2013-09-10','Guardian');
INSERT INTO Player VALUES (76561198000222002,'MapAwareness0','W-EUROPE',    DATE '2016-04-18','Guardian');
INSERT INTO Player VALUES (76561198000222003,'TriHardPudge', 'W-EUROPE',    DATE '2018-08-27','Guardian');
INSERT INTO Player VALUES (76561198000222004,'LastHitKing_Not','W-EUROPE',  DATE '2017-12-01','Guardian');
INSERT INTO Player VALUES (76561198000222005,'TpScrollWhere', 'W-EUROPE',   DATE '2020-05-20','Guardian');
-- Crusader
INSERT INTO Player VALUES (76561198000333001,'SmokeMasterX',  'N-AMERICA',   DATE '2014-01-11','Crusader');
INSERT INTO Player VALUES (76561198000333002,'WardsBuyerRare','N-AMERICA',   DATE '2015-03-22','Crusader');
INSERT INTO Player VALUES (76561198000333003,'RoamingPhantom','N-AMERICA',   DATE '2016-10-09','Crusader');
INSERT INTO Player VALUES (76561198000333004,'BKBorDie',    'N-AMERICA',     DATE '2018-02-14','Crusader');
INSERT INTO Player VALUES (76561198000333005,'FlameStrikeFan','CHINA',   DATE '2019-08-17','Crusader');
-- Archon
INSERT INTO Player VALUES (76561198000444001,'ArchonArcher', 'CHINA',    DATE '2013-06-25','Archon');
INSERT INTO Player VALUES (76561198000444002,'MidOrFeed_v2','FILIPINO' ,    DATE '2015-11-30','Archon');
INSERT INTO Player VALUES (76561198000444003,'SilentSupportPro','S-AMERICA', DATE '2017-04-04','Archon');
INSERT INTO Player VALUES (76561198000444004,'JungleEveryGame','S-AMERICA',  DATE '2018-07-19','Archon');
INSERT INTO Player VALUES (76561198000444005,'DoubleClickDagon','S-AMERICA', DATE '2020-01-07','Archon');
-- Legend
INSERT INTO Player VALUES (76561198000555001,'LegendaryMidLaner','S-AMERICA',DATE '2012-08-01','Legend');
INSERT INTO Player VALUES (76561198000555002,'CoreOrSupport', 'S-AMERICA',   DATE '2014-05-17','Legend');
INSERT INTO Player VALUES (76561198000555003,'Warpgate_Invoker','S-AMERICA', DATE '2016-09-23','Legend');
INSERT INTO Player VALUES (76561198000555004,'HexQueen_Lion','S-AMERICA',    DATE '2017-03-12','Legend');
INSERT INTO Player VALUES (76561198000555005,'Pudge_Hooker_PL','E-EUROPE',  DATE '2019-06-06','Legend');
-- Ancient
INSERT INTO Player VALUES (76561198000666001,'AncientOneSupreme','RUSSIA',DATE '2013-02-28','Ancient');
INSERT INTO Player VALUES (76561198000666002,'VoidComboMaster','RUSSIA',  DATE '2015-10-10','Ancient');
INSERT INTO Player VALUES (76561198000666003,'EgoBoostedCarry','RUSSIA',  DATE '2016-12-31','Ancient');
INSERT INTO Player VALUES (76561198000666004,'SleepySupport',  'RUSSIA',  DATE '2018-04-04','Ancient');
INSERT INTO Player VALUES (76561198000666005,'MorphingDreams','CHINA',   DATE '2020-09-15','Ancient');
-- Divine
INSERT INTO Player VALUES (76561198000777001,'GodlikeInvoker','RUSSIA',   DATE '2012-11-11','Divine');
INSERT INTO Player VALUES (76561198000777002,'SpectreDivine','RUSSIA',    DATE '2014-07-04','Divine');
INSERT INTO Player VALUES (76561198000777003,'MidNightSaber','RUSSIA',    DATE '2015-05-05','Divine');
INSERT INTO Player VALUES (76561198000777004,'UrsaGodMode', 'RUSSIA',     DATE '2017-01-20','Divine');
INSERT INTO Player VALUES (76561198000777005,'QuietButLethal','CHINA',   DATE '2019-03-03','Divine');
-- Immortal
INSERT INTO Player VALUES (76561198000888001,'N0tail_wannabe', 'RUSSIA',  DATE '2011-06-15','Immortal');
INSERT INTO Player VALUES (76561198000888002,'TopNetWorthPlayer','RUSSIA',DATE '2012-02-20','Immortal');
INSERT INTO Player VALUES (76561198000888003,'GH_Copy_Paste', 'RUSSIA',   DATE '2013-04-01','Immortal');
INSERT INTO Player VALUES (76561198000888004,'Crit_Or_Skip','CHINA',     DATE '2014-09-09','Immortal');
INSERT INTO Player VALUES (76561198000888005,'MiraculousAgi','CHINA',    DATE '2015-12-12','Immortal');

COMMIT;

-- ============================================================
--  VERIFICATION
-- ============================================================
SELECT 'Items'   AS entity, COUNT(*) AS total FROM Item
UNION ALL
SELECT 'Heroes'  AS entity, COUNT(*) AS total FROM Hero
UNION ALL
SELECT 'Players' AS entity, COUNT(*) AS total FROM Player;

SELECT primary_attribute, COUNT(*) AS hero_count
FROM Hero GROUP BY primary_attribute ORDER BY hero_count DESC;

SELECT rank, COUNT(*) AS player_count
FROM Player GROUP BY rank
ORDER BY CASE rank
    WHEN 'Herald'   THEN 1 WHEN 'Guardian'  THEN 2
    WHEN 'Crusader' THEN 3 WHEN 'Archon'    THEN 4
    WHEN 'Legend'   THEN 5 WHEN 'Ancient'   THEN 6
    WHEN 'Divine'   THEN 7 WHEN 'Immortal'  THEN 8
END;

SELECT * FROM Hero;
SELECT * FROM Item;
SELECT * FROM Player;



