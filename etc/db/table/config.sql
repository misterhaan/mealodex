create table if not exists config (
	structureVersion tinyint not null default 0,
	dataVersion tinyint not null default 0
);
