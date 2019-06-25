## namesapce Saseul\Api;

통신을 위한 API를 작성한다.

통신 중, 발생할 수 있는 사항은 아래와 같다.
1. DB를 조회.
1. File을 생성.
1. File을 조회.
1. Property (Cache)에 저장

## 주의사항 

직접적으로 DB, File과 연동하지 않도록 한다.

DB, File은 Saseul\Core; 의 소스로 접근한다.