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
  sed 's/tutorial.saseul.net/web/g' ./env.example > ./.env
}

# Code quality
function fix() {
  ./vendor/bin/php-cs-fixer fix --using-cache=no
}

function test() {
  api_exec './vendor/bin/phpunit --coverage-text'
}

function phan() {
  api_exec './dev.sh ci-phan'
}

# SASEUL
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

function ci_phan() {
  PHAN_ALLOW_XDEBUG=1 ./vendor/bin/phan
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
  ci-phan)
    ci_phan
    ;;
  help|*)
    print_help
    exit 0
    ;;
esac
