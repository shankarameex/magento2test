FROM centos:centos7
MAINTAINER shiva.shankar@ameexusa.com
# Centos default image for some reason does not have tools like Wget/Tar/etc so lets add them
RUN yum -y install wget

RUN yum -y install git

#RUN yum install zip

RUN yum -y install which openssh-server php-mysql php-gd php-mcrypt php-zip php-xml php-iconv php-curl php-soap php-simplexml php-pdo php-dom php-cli php-fpm httpd

RUN yum -y install mariadb-server

#ADD default.conf /etc/nginx/conf.d/default.conf

RUN chkconfig php-fpm on

#RUN chkconfig nginx on

RUN systemctl enable mariadb

#RUN systemctl start mariadb

#RUN systemctl start httpd.service
RUN systemctl enable httpd.service

#Magento Project Installation
RUN cd /var/www/

RUN wget http://dev56.ameexopensrc.com/internal/internal.zip
RUN unzip internal.zip ./
RUN rm -rf internal.zip