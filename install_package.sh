#!/usr/bin/env bash
# TODO: Delete this FILE.
# reset variables
MONGOPATH="/etc/yum.repos.d/mongodb-org-4.0.repo"
HOMEPATH="/home/ec2-user"

SASEUL_PATH="/var/saseul"

SASEULD=$SASEUL_PATH/"src/saseuld"
SASEUL_SCRIPT=$SASEUL_PATH/"src/saseul_script"
SASEUL_HTTPD_CONF=$SASEUL_PATH/"conf/httpd/saseul-api-httpd.conf"
SASEUL_SERVICE=$SASEUL_PATH/"bin/saseuld.service"
SASEUL_SOURCE=$SASEUL_PATH/"src/Saseul"
SASEUL_SOURCE_DEFAULT=$SASEUL_PATH/"sourcedata/SaseulDefault"

TARGET_SASEUL_SCRIPT="/usr/bin/saseul_script"
TARGET_HTTPD_CONF="/etc/httpd/conf.d/saseul.conf"
TARGET_SERVICE="/etc/init.d/saseuld"

SASEUL_BLOCK_PATH=$SASEUL_PATH/"blockdata"
SASEUL_BLOCK_APATH=$SASEUL_BLOCK_PATH/"apichunks"
SASEUL_BLOCK_BPATH=$SASEUL_BLOCK_PATH/"broadcastchunks"
SASEUL_BLOCK_TPATH=$SASEUL_BLOCK_PATH/"transactions"

function CheckPackage() {
    GREP_STRING=$1
    NAME_STRING=$2
    PACK_EXISTS=$(yum list | grep "$GREP_STRING")

    if [[ -z ${PACK_EXISTS} ]]
    then
        echo "$2 package does not exists. "
        echo "install fail. "
        exit
    else
        echo "$2 package exists. "
    fi
}

function CheckCommand() {
    COMM_STRING=$1
    COMM_EXISTS=$(command -v "$COMM_STRING")

    if [[ -z ${PACK_EXISTS} ]]
    then
        echo "$COMM_STRING does not exists. "
        echo "install fail. "
        exit
    fi
}

function InstallPackage() {
    GREP_STRING=$1
    PACK_STRING=$2
    NAME_STRING=$3

    NODE_IS_INSTALLED=
    NODE_IS_INSTALLED=$(yum list installed | grep "$GREP_STRING")

    if [[ -z ${NODE_IS_INSTALLED} ]]
    then
        yum install "$2" -y
    else
        echo "$3 package is installed already. "
    fi
}

# Check if the base package exists.
CheckPackage "^git" "git"
CheckPackage "^gcc72" "gcc72"
CheckPackage "^httpd24" "httpd24*"
CheckPackage "^memcached" "memcached"
CheckPackage "^php" "php"
CheckPackage "^openssl" "openssl"

# Check if mongodb repository exists.
if [[ -z $(yum list | grep "^mongodb") ]]
then
    echo "[mongodb-org-4.0]" > "$MONGOPATH"
    echo "name=MongoDB Repository" >> "$MONGOPATH"
    echo "baseurl=https://repo.mongodb.org/yum/amazon/2013.03/mongodb-org/4.0/x86_64/" >> "$MONGOPATH"
    echo "gpgcheck=1" >> "$MONGOPATH"
    echo "enabled=1" >> "$MONGOPATH"
    echo "gpgkey=https://www.mongodb.org/static/pgp/server-4.0.asc" >> "$MONGOPATH"
    CheckPackage "^mongodb" "mongodb"
else
    echo "mongodb package exists. "
fi

# Install pacakges.
InstallPackage "^git" "git" "git"
InstallPackage "^gcc" "gcc72*" "gcc"
InstallPackage "^httpd" "httpd24*" "httpd"
InstallPackage "^memcached" "memcached" "memcached"
InstallPackage "^mongodb" "mongodb*" "mongodb"
InstallPackage "^php" "php71*" "php"
InstallPackage "^openssl-devel" "openssl-*" "openssl"

# Check pecl7.
CheckCommand "pecl7"

# Install php-mongodb extension.
if [[ -z $(grep "mongodb.so" /etc/php.ini ) ]]
then
    pecl7 install mongodb
    echo ""
    echo "; Added by saseul team " $(date) >> /etc/php.ini
    echo "extension=mongodb.so" >> /etc/php.ini
else
    echo "php-mongodb extension exists. "
fi

# Install php-ed25519 extension.
if [[ -z $(grep "ed25519.so" /etc/php.ini ) ]]
then
    cd "$HOMEPATH"
    git clone https://github.com/encedo/php-ed25519-ext.git
    cd php-ed25519-ext
    phpize
    ./configure
    make
    make install
    echo "extension=ed25519.so" >> /etc/php.ini
else
    echo "php-ed25519 extension exists. "
fi

# Add saseul user.
if [[ -z $(cat /etc/passwd | grep "saseul" ) ]]
then
    groupadd saseul

    useradd -s /sbin/nologin saseul

    usermod -a -G saseul apache
    usermod -a -G saseul saseul
fi

# Install composer.
if [[ -z $(command -v "composer") ]]
then
    CheckCommand "php"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    mv composer.phar /usr/bin/composer
fi

if [ -e $SASEULD ] ; then
    chmod +x $SASEULD
    echo "saseuld is now exectable. "
fi

if [ -e $SASEUL_SCRIPT ] ; then
    chmod +x $SASEUL_SCRIPT
    echo "saseul_script is now exectable. "
fi

if [ -e $SASEUL_SERVICE ] ; then
    chmod +x $SASEUL_SERVICE
    echo "saseul_service is now exectable. "
fi

echo ""

if [ ! -e $TARGET_SASEUL_SCRIPT ] ; then
    ln -s $SASEUL_SCRIPT $TARGET_SASEUL_SCRIPT
    echo "A executable script created. "
fi

if [ ! -e $TARGET_HTTPD_CONF ] ; then
    ln -s $SASEUL_HTTPD_CONF $TARGET_HTTPD_CONF
    echo "Apache config file created. "
fi

if [ ! -e $TARGET_SERVICE ] ; then
    ln -s $SASEUL_SERVICE $TARGET_SERVICE
    echo "Service file created. "
fi

echo ""

if [ ! -d $SASEUL_BLOCK_PATH ] ; then
    TMP_META=($(ls -ld $SASEULD))
    TMP_GROUP="${TMP_META[3]}"
    TMP_USER="${TMP_META[2]}"

    mkdir $SASEUL_BLOCK_PATH
    chown -Rf $TMP_USER:$TMP_GROUP $SASEUL_BLOCK_PATH
    chmod -Rf g+w $SASEUL_BLOCK_PATH

    TMP_META=
    TMP_GROUP=
    TMP_USER=

    echo "Block directory created. "
    echo ""
fi

if [ -d $SASEUL_BLOCK_PATH ] ; then
    TMP_META=($(ls -ld $SASEUL_BLOCK_PATH))
    TMP_GROUP="${TMP_META[3]}"
    TMP_USER="${TMP_META[2]}"

    if [ ! -d $SASEUL_BLOCK_APATH ] ; then
        mkdir $SASEUL_BLOCK_APATH
        chown -Rf $TMP_USER:$TMP_GROUP $SASEUL_BLOCK_APATH
        chmod -Rf g+w $SASEUL_BLOCK_APATH
    fi

    if [ ! -d $SASEUL_BLOCK_BPATH ] ; then
        mkdir $SASEUL_BLOCK_BPATH
        chown -Rf $TMP_USER:$TMP_GROUP $SASEUL_BLOCK_BPATH
        chmod -Rf g+w $SASEUL_BLOCK_BPATH
    fi

    if [ ! -d $SASEUL_BLOCK_TPATH ] ; then
        mkdir $SASEUL_BLOCK_TPATH
        chown -Rf $TMP_USER:$TMP_GROUP $SASEUL_BLOCK_TPATH
        chmod -Rf g+w $SASEUL_BLOCK_TPATH
    fi

    TMP_META=
    TMP_GROUP=
    TMP_USER=

    echo "Chunk directories created. "
    echo ""
fi

# Service start
service httpd restart
service memcached restart
service mongod restart
chkconfig httpd on
chkconfig memcached on
chkconfig mongod on

echo ""

ln -s "$SASEUL_SOURCE_DEFAULT" "$SASEUL_SOURCE"
chown -Rf saseul:saseul "$SASEUL_PATH"
chown -Rf saseul:saseul "$SASEUL_SOURCE"

service saseuld restart
sleep 3
saseul_script Reset -r
