# SASEUL Core

[![Maintainability][maintainability-badges]][maintainability-repos] | [![Test Coverage][cov-badges]][cov-repos]


## TODO

### 우선적으로 개선해야할 부분
> 고쳐야할 파일은 TODO 표시해둠.

* [ ] 압축을 위해 shell_exec 하는 부분을 없애야 함.
* [ ] Round 체크와 Tracker collect 합치면 속도 10~20% 빨라짐
* [ ] Round 체크와 Tracker collect Async로 분리하면 속도 20~30% 빨라짐.
* [ ] registerRequest queue로 로직 변경해야 함.

### 필요하면 고칠 수 있는 부분

* [ ] env 로직 수정
* [ ] Validator 고의 트롤시 ban하는거 public/private 옵션 줄 수 있도록 해야함.

## Node 실행 순서

* `docker-compose run --rm api sh -c "cd src; ./saseul_script Reset"`

* `docker-compose up -d`
  * 실행하게되면 `saseuld.pid`가 생성된다. 항상 지워주도록 하자.


## Release Note

### v.1.0.0.2

1. 자신의 Host 등록하는 절차 추가;

### v.1.0.0.1

1. registerRequest Queue 초기화 추가;


## Dev Command

### `compoer`

```script
scripts:
  docker-build   Build SASEUL docker image
  docker-up      사슬에 연관된 도커 이미지들 대몬 형식으로 실행
  docker-down    사슬에 연관된 도커 이미지 관련 내용 삭제
  docker-log     서비스 로그 확인
  make-env       env 파일 생성
  add            패키지 추가
  local-install  패키지 설치
  local-update   패키지 업데이트
  node-clean     SASEUL 노드에 관련된 정보들을 전부 삭제
  phan           정적 분석 실행( 수정 X )
  fix            문법 오류 수정
  test           테스트 코드 실행
  ci-phan        [CI] 정적 분석기
  ci-fix         [CI] 문법 오류 확인
  ci-test        [CI] 테스트 코드 실행
```

* 추가한 명령을 확인하려면 `composer run -l` 명령을 이용한다.

[maintainability-badges]: https://api.codeclimate.com/v1/badges/ab103c8f70fafe7ed3b6/maintainability
[maintainability-repos]: https://codeclimate.com/repos/5d47e92991b75a019f001554/maintainability
[cov-badges]: https://api.codeclimate.com/v1/badges/ab103c8f70fafe7ed3b6/test_coverage
[cov-repos]: https://codeclimate.com/repos/5d47e92991b75a019f001554/test_coverage
