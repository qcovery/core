FROM httpd:2.4

RUN apt-get update && apt-get install -y \
        nano

COPY conf/httpd.conf /usr/local/apache2/conf/httpd.conf