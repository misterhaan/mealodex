create table if not exists prep (
	id tinyint unsigned not null primary key auto_increment,
	name varchar(32) not null,
	unique(name),
	description text not null
);
