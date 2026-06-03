FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    unixodbc \
    unixodbc-dev \
    tdsodbc \
    freetds-bin \
    freetds-dev \
    bash \
    ca-certificates \
    git \
    unzip \
    && docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr \
    && docker-php-ext-install pdo_odbc \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY scripts/configure-odbc-driver.sh /usr/local/bin/configure-odbc-driver.sh
RUN chmod +x /usr/local/bin/configure-odbc-driver.sh \
    && /usr/local/bin/configure-odbc-driver.sh

WORKDIR /app
