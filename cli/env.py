from pathlib import Path

from invoke import Collection, task

REPO_ROOT = Path.cwd()


@task()
def make(ctx):
    """개발용 SASEUL Core 에서 사용할 env 파일을 생성한다."""
    ctx.run(f"cp {REPO_ROOT / 'env.example'} {REPO_ROOT / '.env'}")


# 위에다 task 입력하고 아래에는 사용할 명령어 추가
ns = Collection()
ns.add_task(make)
