FROM percona:8.0.29-21-centos
MAINTAINER Cosmin-Romeo TANASE <tanasecosminromeo@gmail.com>

USER root
RUN yum update \
    && yum -y install epel-release \
    && yum makecache --refresh --enablerepo="ol8_developer_EPEL" \
    && yum -y install pv bc supervisor openssh-clients procps sudo \
    && yum -y upgrade \
    && yum clean all \
    && rm -rf /var/cache/yum

COPY percona.sh /usr/local/bin/percona
RUN chmod +x /usr/local/bin/percona

COPY entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

ENTRYPOINT ["entrypoint"]