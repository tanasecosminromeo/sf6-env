FROM percona:8.0.43-34
LABEL maintainer="Cosmin-Romeo TANASE <cosmin@tanase.dev>"

USER root

RUN curl -O https://dl.fedoraproject.org/pub/epel/epel-release-latest-9.noarch.rpm

RUN rpm -ivh epel-release-latest-9.noarch.rpm

RUN microdnf update -y \
    && microdnf install -y bc supervisor openssh-clients procps sudo pv supervisor \
    && microdnf clean all

COPY percona.sh /usr/local/bin/percona
RUN chmod +x /usr/local/bin/percona

COPY entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

ENTRYPOINT ["entrypoint"]