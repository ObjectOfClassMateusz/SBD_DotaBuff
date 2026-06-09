
CREATE INDEX hero_played_steam_id_index ON Hero_Played (steam_id);

CREATE INDEX hero_played_hero_id_index ON Hero_Played (hero_id);

CREATE INDEX hero_played_position_index ON Hero_Played (position);

CREATE INDEX match_game_time_index ON Match_Game (match_time DESC);

CREATE INDEX player_rank_index ON Player (rank);

CREATE INDEX hero_primary_attribute_index ON Hero (primary_attribute);

COMMIT;