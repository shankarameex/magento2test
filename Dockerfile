FROM centos:centos7
MAINTAINER shiva.shankar@ameexusa.com
# Centos default image for some reason does not have tools like Wget/Tar/etc so lets add them
RUN yum -y install wget

RUN yum -y install git

RUN yum -y install which openssh-server php-mysql php-gd php-mcrypt php-zip php-xml php-iconv php-curl php-soap php-simplexml php-pdo php-dom php-cli php-fpm nginx

RUN yum -y install mariadb-server

ADD default.conf /etc/nginx/conf.d/default.conf

RUN chkconfig php-fpm on

#RUN chkconfig nginx on

#docker run --name mariadbcontainer -e MYSQL_ROOT_PASSWORD=ameexusa -d mariadb:10.1

#Magento Project Installation
RUN cd /var/www/

RUN git clone git@github.com:paimpozhil/docker-magento.git