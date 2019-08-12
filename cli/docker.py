from pathlib import Path

from invoke import Collection, task

REPO_ROOT = Path.cwd()


@task()
def down(ctx):
    """Docker 네트워크와 이미지를 terminate 한다."""
    ctx.run(f"docker-compose -f {REPO_ROOT / 'docker-compose.yml'} down")


# 위에다가 task를 입력하고 아래에는 사용할 명령어 추가
ns = Collection()
ns.add_task(down)
