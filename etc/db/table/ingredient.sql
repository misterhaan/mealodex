create table if not exists ingredient (
	recipe smallint unsigned not null,
	foreign key(recipe) references recipe(id) on update cascade on delete cascade,
	sort smallint unsigned not null default 0 comment 'Controls the order recipe ingredients are listed (smallest first)',
	primary key(recipe, sort),
	item smallint unsigned not null,
	foreign key(item) references item(id) on update cascade on delete cascade,
	amount float not null,
	unit tinyint unsigned not null,
	foreign key(unit) references unit(id) on update cascade on delete cascade,
	prep tinyint unsigned,
	foreign key(prep) references prep(id)
);
