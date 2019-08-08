#! /usr/bin/env bash

# message
function error_msg() {
    RED='\033[0;31m'
    NC='\033[0m'
    echo -e "\n${RED}${1}${NC}\n"
}

if [[ (! -f ./.env) && (-z $1) ]]; then
    error_msg ".env 파일을 setenv 나 setenv_other 명령을 이용하여 생성 해주십시오."
fi

function check_env_command() {
    if [[ ! -f ./.env ]]; then
        error_msg ".env 파일을 setenv 나 setenv_other 명령을 이용하여 생성 해주십시오."
        print_help
        exit 1
    fi
}

function print_help() {
    egrep '^ *# ' ./dev.sh | sed "s,^ *,$0," | column -t -s '#'
}

# Common
function api_exec() {
    docker-compose run --rm api bash -c "$1"
}

# Env
function setenv() {
    if [[ "$1" == 'other' ]]; then
        if [[ (-z $2) || (-z $3) ]]; then
            error_msg "Input Genesis host name or Node host ip."
            print_help
            exit 1
        fi

        echo -e 'GENESIS_HOST='"$2"'\nNODE_HOST='"$3" > ./.env
    fi

    cat ./env.example | sed 's/tutorial.saseul.net/web/g' > ./.env
}

# Docker
function docker_logs() {
    docker-compose logs -f $1
}

function build() {
    DOCKER_BUILDKIT=1 docker build --rm -t saseul-core .
}

function up() {
    docker-compose up -d
}

function down() {
    docker-compose down
}

function logs() {
    if [[ -z "$1" ]]; then
        echo 'container service 이름을 입력해주세요'
        exit 1
    fi

    docker_logs $1
}

# composer
function composer_install() {
  composer install --ignore-platform-reqs && composer dump-autoload
}

function composer_update() {
  composer update --ignore-platform-reqs
}

# Code quality
function fix() {
  ./vendor/bin/php-cs-fixer fix --using-cache=no
}

function test() {
  api_exec './vendor/bin/phpunit --coverage-text'
}

function phan() {
  api_exec 'PHAN_ALLOW_XDEBUG=1 ./vendor/bin/phan'
}

# SASEUL
function node_data_cleanup() {
  rm -rf ./blockdata/apichunks/*
  rm -rf ./blockdata/broadcastchunks/*
  rm -rf ./blockdata/transactions/*
  rm -rf ./blockdata/txarchives/*
  rm -rf ./data/db/*

  touch ./blockdata/apichunks/.keep
  touch ./blockdata/broadcastchunks/.keep
  touch ./blockdata/transactions/.keep
  touch ./blockdata/txarchives/.keep
  echo "Cleanup SASEUL data"
}

function node_genesis() {
    up
    sleep 5
    api_exec "
    cd script
    ./saseul_script Reset
    ./saseul_script Genesis
    "
    sleep 5
    up
    echo "Genesis.."
}

function ci_fix() {
    ./vendor/bin/php-cs-fixer fix --using-cache=no --dry-run --diff
}

function ci_test() {
    ./vendor/bin/phpunit --coverage-text --coverage-clover build/logs/clover.xml
}

case $1 in
    setenv)
        # setenv    # 테스트용으로 혼자 띄워서 설정할때.
        setenv
        ;;
    setenv_other)
        # setenv_other [genesis_host_name] [node_id]    # 기존 노드에 붙거나,
        setenv other $2 $3
        ;;
    build)
        check_env_command
        # build     # Docker 이미지를 생성한다.
        build
        ;;
    up)
        check_env_command
        # up    # 연관된 컨테이너를 실행한다.
        up
        ;;
    buildup)
        check_env_command
        # buildup   # Docker 이미지를 빌드하고 패키지를 설치한 뒤, 모든 컨테이너를 실행한다.
        build
        composer
        up
        ;;
    down)
        check_env_command
        # down  # 실행한 컨테이너를 정지(halt)시킨다.
        down
        ;;
    logs)
        check_env_command
        # logs [api|node|web|memcached|mongo]  # 각 서비스에 대한 로그를 확인한다.
        logs $2
        ;;
  # composer
  install)
    # install   # composer 패키지를 설치합니다.
    composer_install
    ;;
  update)
    # update    # composer 패키지를 업데이트 합니다.
    composer_update
    ;;
  # Code quality
  test)
    # test  # 각 컴포넌트 별로 테스트를 진행합니다.
    test
    ;;
  fix)
    # fix   # 각 컴포넌트 별로 fixer를 진행합니다.
    fix
    ;;
  phan)
    # phan  # 각 컨포넌트 별로 정적 분석을 합니다.
    phan
    ;;
  cleanup)
    # cleanup  # node 생성에 필요한 정보들을 삭제한다.
    down
    node_data_cleanup
    ;;
    genesis)
        check_env_command
        # genesis  # saseul origin 네트워크를 stand alone 으로 실행할 수 있도록 띄운다.
        node_genesis
        ;;
    ci-test)
        ci_test
        ;;
    ci-fix)
        ci_fix
        ;;
    help|*)
        print_help
        exit 0
        ;;
esac
