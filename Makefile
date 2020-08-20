build:
	docker build -t incubator-mongodb .

exec:
	docker run --rm -it \
		-v $(CURDIR):/app \
		--network dev \
		incubator-mongodb bash

db:
	docker run --rm -d \
		--network dev \
		--name mongodb \
		-p 27017:27017 \
		mongo

test:
	docker run --rm -it \
		-v $(CURDIR):/app \
		--network dev \
		incubator-mongodb php -d display_errors=On -d error_reporting=E_ALL vendor/bin/codecept run
