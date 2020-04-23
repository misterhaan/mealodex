create table if not exists recipe (
	id smallint unsigned primary key not null auto_increment,
	name varchar(64) not null,
	unique(name),
	lastServed date,
	complexity tinyint unsigned not null default 0 comment '1-3 are easy, normal, involved.  0 is unspecified',
	servings tinyint unsigned not null default 0 comment '0 is unspecified',
	instructions text not null
);
