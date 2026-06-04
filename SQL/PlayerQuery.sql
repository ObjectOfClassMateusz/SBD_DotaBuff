
CREATE OR REPLACE TYPE FoundPlayer AS OBJECT (
    id NUMBER,
    nickname VARCHAR2(64)
);
/

CREATE OR REPLACE TYPE FoundPlayerList AS TABLE OF FoundPlayer;
/


CREATE OR REPLACE TYPE PlayerQueryInfo AS OBJECT (
    id NUMBER,
    nickname VARCHAR2(64),
    region VARCHAR2(64),
    account_created DATE,
    rank VARCHAR(64)
);
/


CREATE OR REPLACE PACKAGE PlayerQuery
AS
    
    FUNCTION SearchByNick(v_nick IN Player.nickname%TYPE) RETURN FoundPlayerList PIPELINED;
    
    FUNCTION GetPlayerInfo(v_id IN Player.steam_id%TYPE) RETURN PlayerQueryInfo;
    
END;
/


CREATE OR REPLACE PACKAGE BODY PlayerQuery
AS
    
    FUNCTION SearchByNick (v_nick IN Player.nickname%TYPE) RETURN FoundPlayerList PIPELINED
    IS
    BEGIN
        
        FOR v_rec IN (SELECT steam_id, nickname
                      FROM Player
                      WHERE upper(nickname) LIKE ('%' || upper(v_nick) || '%')
                      ORDER BY nickname) LOOP
            
            PIPE ROW (FoundPlayer(v_rec.steam_id, v_rec.nickname));
            
        END LOOP;
    END;
    
    FUNCTION GetPlayerInfo(v_id IN Player.steam_id%TYPE) RETURN PlayerQueryInfo
    AS
    BEGIN
        FOR v_rec IN (SELECT * FROM Player
                      WHERE steam_id = v_id) LOOP
            
            RETURN PlayerQueryInfo(v_rec.steam_id, v_rec.nickname, v_rec.region, v_rec.account_created, v_rec.rank);
            
        END LOOP;
    END;
    
END;
/

COMMIT;


SELECT * FROM table(PlayerQuery.SearchByNick('Alliance'));

SELECT PlayerQuery.GetPlayerInfo(76561198000222001).region FROM dual;

