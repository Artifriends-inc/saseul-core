## namesapce Saseul\Daemon;

사슬 데몬의 역할별 세부 로직을 작성한다.

LightNode와 Supervisor는 로직이 같다.

LightNode는 블록을 받기만 하며, Arbiter의 소스코드가 변경되면 자신의 소스코드를 업데이트한다.

단, Env에 따라 업데이트와 정지 방침을 결정할 수 있다.

Validator는 블록을 생성하며, 소스를 자동으로 업데이트하지 않는다.

Validator는 중간 블록이 소실되더라도 합의에 문제가 없다.

Arbiter는 Block Generation 관리를 담당한다.


## 주의사항 

직접적으로 DB, File과 연동하지 않고, 통신 또한 직접 하지 않는다.

DB, File은 Saseul\Core; 의 소스로 접근한다.

통신은 Saseul\Consensus;의 소스로 진행한다.