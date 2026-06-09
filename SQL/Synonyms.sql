CREATE PUBLIC SYNONYM Item FOR C##app_admin.Item;
CREATE PUBLIC SYNONYM Hero FOR C##app_admin.Hero;
CREATE PUBLIC SYNONYM Player FOR C##app_admin.Player;
CREATE PUBLIC SYNONYM Hero_Played FOR C##app_admin.Hero_Played;
CREATE PUBLIC SYNONYM Team FOR C##app_admin.Team;
CREATE PUBLIC SYNONYM Match_Game FOR C##app_admin.Match_Game;
CREATE PUBLIC SYNONYM Patch_Info FOR C##app_admin.Patch_Info;
CREATE PUBLIC SYNONYM Patch_Hero_Change FOR C##app_admin.Patch_Hero_Change;
CREATE PUBLIC SYNONYM Patch_Item_Change FOR C##app_admin.Patch_Item_Change;

CREATE PUBLIC SYNONYM HeroPlayedDTO FOR C##app_admin.HeroPlayedDTO;
CREATE PUBLIC SYNONYM TeamDTO FOR C##app_admin.TeamDTO;
CREATE PUBLIC SYNONYM DatabaseAdministration FOR C##app_admin.DatabaseAdministration;
CREATE PUBLIC SYNONYM ItemPopularity FOR C##app_admin.ItemPopularity;
CREATE PUBLIC SYNONYM ItemPopularityList FOR C##app_admin.ItemPopularityList;
CREATE PUBLIC SYNONYM PatchChangeDTO FOR C##app_admin.PatchChangeDTO;
CREATE PUBLIC SYNONYM PatchChangeList FOR C##app_admin.PatchChangeList;
CREATE PUBLIC SYNONYM GeneralInfo FOR C##app_admin.GeneralInfo;
CREATE PUBLIC SYNONYM RankedHero FOR C##app_admin.RankedHero;
CREATE PUBLIC SYNONYM RankedHeroList FOR C##app_admin.RankedHeroList;
CREATE PUBLIC SYNONYM ChangedHero FOR C##app_admin.ChangedHero;
CREATE PUBLIC SYNONYM HeroStatistics FOR C##app_admin.HeroStatistics;
CREATE PUBLIC SYNONYM QueriedMatch FOR C##app_admin.QueriedMatch;
CREATE PUBLIC SYNONYM MatchList FOR C##app_admin.MatchList;
CREATE PUBLIC SYNONYM HeroFrequency FOR C##app_admin.HeroFrequency;
CREATE PUBLIC SYNONYM HeroFrequencyList FOR C##app_admin.HeroFrequencyList;
CREATE PUBLIC SYNONYM PlayerStatistics FOR C##app_admin.PlayerStatistics;

CREATE PUBLIC SYNONYM seq_item_id FOR C##app_admin.seq_item_id;
CREATE PUBLIC SYNONYM seq_hero_id FOR C##app_admin.seq_hero_id;
CREATE PUBLIC SYNONYM seq_heroplayed_id FOR C##app_admin.seq_heroplayed_id;
CREATE PUBLIC SYNONYM seq_match_id FOR C##app_admin.seq_match_id;
CREATE PUBLIC SYNONYM seq_Patch_Hero_Change_id FOR C##app_admin.seq_Patch_Hero_Change_id;
CREATE PUBLIC SYNONYM seq_Patch_Item_Change_id FOR C##app_admin.seq_Patch_Item_Change_id;
CREATE PUBLIC SYNONYM seq_Patch_id FOR C##app_admin.seq_Patch_id;


COMMIT;