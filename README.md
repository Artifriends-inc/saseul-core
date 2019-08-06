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


[maintainability-badges]: https://api.codeclimate.com/v1/badges/ab103c8f70fafe7ed3b6/maintainability
[maintainability-repos]: https://codeclimate.com/repos/5d47e92991b75a019f001554/maintainability
[cov-badges]: https://api.codeclimate.com/v1/badges/ab103c8f70fafe7ed3b6/test_coverage
[cov-repos]: https://codeclimate.com/repos/5d47e92991b75a019f001554/test_coverage
