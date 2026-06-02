
CREATE OR REPLACE TYPE QueriedMatch IS OBJECT (
    id NUMBER
);
/

CREATE OR REPLACE TYPE MatchList IS TABLE OF QueriedMatch;
/

CREATE OR REPLACE TYPE HeroFrequency IS OBJECT (
    id NUMBER,
    num_plays NUMBER
);
/

CREATE OR REPLACE TYPE HeroFrequencyList IS TABLE OF HeroFrequency;
/


CREATE OR REPLACE PACKAGE PlayerStatistics AS
    
    FUNCTION QueryLastMatches(v_playerId IN Player.steam_id%TYPE,
                              v_count IN INTEGER, v_offset IN INTEGER,
                              v_ranked IN Match_Game.is_ranked%TYPE,
                              v_startTime IN DATE, v_endTime IN DATE) RETURN MatchList PIPELINED;
                              
    FUNCTION PlayerWinRate(v_playerId IN Player.steam_id%TYPE,
                           v_heroId IN Hero.id%TYPE,
                           v_ranked IN Match_Game.is_ranked%TYPE,
                           v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER;
                           
    FUNCTION AvgKda(v_playerId IN Player.steam_id%TYPE,
                    v_ranked IN Match_Game.is_ranked%TYPE,
                    v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER;
                    
    FUNCTION MostFrequentlyPlayedHeros(v_playerId IN Player.steam_id%TYPE,
                                       v_count IN INTEGER,
                                       v_ranked IN Match_Game.is_ranked%TYPE,
                                       v_startTime IN DATE, v_endTime IN DATE) RETURN HeroFrequencyList PIPELINED;
                                       
    FUNCTION NettoWorth(v_playerId IN Player.steam_id%TYPE,
                        v_matchId IN Match_Game.id%TYPE) RETURN NUMBER;
    
END PlayerStatistics;
/

CREATE OR REPLACE PACKAGE BODY PlayerStatistics AS

    FUNCTION QueryLastMatches(v_playerId IN Player.steam_id%TYPE,
                              v_count IN INTEGER, v_offset IN INTEGER,
                              v_ranked IN Match_Game.is_ranked%TYPE,
                              v_startTime IN DATE, v_endTime IN DATE) RETURN MatchList PIPELINED
    AS
        CURSOR c_cur IS (SELECT id
                         FROM (SELECT id FROM Match_Game m
                              WHERE is_ranked = v_ranked AND
                                    match_time >= v_startTime AND match_time <= v_endTime AND
                                    EXISTS (SELECT 1
                                            FROM Team t, Hero_Played h
                                            WHERE (t.id = m.team1_id OR t.id = m.team2_id) AND
                                                  (h.id = t.hp1 OR
                                                   h.id = t.hp2 OR
                                                   h.id = t.hp3 OR
                                                   h.id = t.hp4 OR
                                                   h.id = t.hp5) AND
                                                   h.steam_id = v_playerId)
                              ORDER BY match_time DESC)
                         WHERE rownum > v_offset AND rownum <= v_offset+v_count);
    BEGIN
            
        FOR v_rec IN c_cur LOOP
            PIPE ROW (QueriedMatch(v_rec.id));
        END LOOP;
    END;
    
    
    FUNCTION PlayerWinRate(v_playerId IN Player.steam_id%TYPE,
                           v_heroId IN Hero.id%TYPE,
                           v_ranked IN Match_Game.is_ranked%TYPE,
                           v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER
    AS
        v_numWon NUMBER := 0;
        v_numTotal NUMBER := 0;
        
        v_playerTeamId Team.id%TYPE;
        
        CURSOR c_cur IS (SELECT m.id matchId, t.id teamId, winner_id winnerId
                         FROM Match_Game m, Team t
                         WHERE is_ranked = v_ranked AND
                               match_time >= v_startTime AND match_time <= v_endTime AND
                               (t.id = m.team1_id OR t.id = m.team2_id) AND
                               EXISTS (SELECT 1
                                       FROM Hero_Played h
                                       WHERE (h.id = t.hp1 OR
                                              h.id = t.hp2 OR
                                              h.id = t.hp3 OR
                                              h.id = t.hp4 OR
                                              h.id = t.hp5) AND
                                              h.steam_id = v_playerId AND
                                              h.hero_id = v_heroId));
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
    
    FUNCTION AvgKda(v_playerId IN Player.steam_id%TYPE,
                    v_ranked IN Match_Game.is_ranked%TYPE,
                    v_startTime IN DATE, v_endTime IN DATE) RETURN NUMBER
    AS
        v_avgkda NUMBER := 0;
    BEGIN
        SELECT avg(kda) INTO v_avgkda
        FROM Match_Game m, Team t, Hero_Played h
        WHERE is_ranked = v_ranked AND
              match_time >= v_startTime AND match_time <= v_endTime AND
              (t.id = m.team1_id OR t.id = m.team2_id) AND
              (h.id = t.hp1 OR
               h.id = t.hp2 OR
               h.id = t.hp3 OR
               h.id = t.hp4 OR
               h.id = t.hp5) AND
              h.steam_id = v_playerId;
    
        RETURN v_avgkda;
    END;
    
    
    FUNCTION MostFrequentlyPlayedHeros(v_playerId IN Player.steam_id%TYPE,
                                       v_count IN INTEGER,
                                       v_ranked IN Match_Game.is_ranked%TYPE,
                                       v_startTime IN DATE, v_endTime IN DATE) RETURN HeroFrequencyList PIPELINED
    AS
        CURSOR c_cur IS (SELECT heroId, numberOfPlays FROM 
                         (SELECT h.hero_id heroId, count(h.id) numberOfPlays
                         FROM Match_Game m, Team t, Hero_Played h
                         WHERE is_ranked = v_ranked AND
                               match_time >= v_startTime AND match_time <= v_endTime AND
                               (t.id = m.team1_id OR t.id = m.team2_id) AND
                               (h.id = t.hp1 OR
                                h.id = t.hp2 OR
                                h.id = t.hp3 OR
                                h.id = t.hp4 OR
                                h.id = t.hp5) AND
                               h.steam_id = v_playerId
                         GROUP BY h.hero_id
                         ORDER BY numberOfPlays DESC)
                         WHERE rownum <= v_count);
    BEGIN
        FOR v_rec IN c_cur LOOP
            PIPE ROW (HeroFrequency(v_rec.heroId, v_rec.numberOfPlays));
        END LOOP;
    END;
    
    
    FUNCTION NettoWorth(v_playerId IN Player.steam_id%TYPE,
                        v_matchId IN Match_Game.id%TYPE) RETURN NUMBER
    AS
        v_netto NUMBER := 0;
    BEGIN
        SELECT netto INTO v_netto
        FROM Match_Game m, Team t, Hero_Played h
        WHERE m.id = v_matchId AND
              (t.id = m.team1_id OR t.id = m.team2_id) AND
              (h.id = t.hp1 OR
               h.id = t.hp2 OR
               h.id = t.hp3 OR
               h.id = t.hp4 OR
               h.id = t.hp5) AND
              h.steam_id = v_playerId;
        
        RETURN v_netto;
    END;
    

END PlayerStatistics;
/

COMMIT;



SELECT * FROM table(PlayerStatistics.QueryLastMatches(76561198000777002, 10, 0, 0, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')));

SELECT PlayerStatistics.PlayerWinRate(76561198000777002, (SELECT id FROM Hero WHERE name='Spectre'), 0, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) || '%' FROM dual;
SELECT PlayerStatistics.PlayerWinRate(76561198000888002, (SELECT id FROM Hero WHERE name='Phantom Assassin'), 0, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) || '%' FROM dual;

SELECT PlayerStatistics.AvgKda(76561198000888002, 0, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')) FROM dual;

SELECT * FROM table(PlayerStatistics.MostFrequentlyPlayedHeros(76561198000777002, 10, 0, TO_DATE('2005-05-05'), TO_DATE('2027-05-05')));

SELECT PlayerStatistics.NettoWorth(76561198000777002, 2) FROM dual;
