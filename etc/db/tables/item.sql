create table if not exists item (
	id smallint unsigned primary key not null auto_increment,
	name varchar(32) not null,
	unique(name)
);
