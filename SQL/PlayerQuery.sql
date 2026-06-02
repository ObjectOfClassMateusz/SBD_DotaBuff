
CREATE OR REPLACE PACKAGE PlayerQuery
AS
    
    FUNCTION SearchByNick (v_nick IN Player.nickname%TYPE) RETURN Player.steam_id%TYPE;
    
    -- FUNCTION GetPlayerInfo(v_id IN Player.steam_id%TYPE) RETURN ;
    
END;
/

CREATE OR REPLACE PACKAGE BODY PlayerQuery
AS
    
    FUNCTION SearchByNick (v_nick IN Player.nickname%TYPE) RETURN Player.steam_id%TYPE
    IS
        v_id Player.steam_id%TYPE;
    BEGIN
        SELECT steam_id INTO v_id
        FROM Player
        WHERE upper(nickname) = upper(v_nick);
        
        RETURN v_id;
        
    EXCEPTION
    WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT_LINE('Could not find player with nickname: ' || v_nick);
        RETURN NULL;
    END;
    
END;
/

