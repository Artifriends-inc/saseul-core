from pathlib import Path

from invoke import Collection, task

REPO_ROOT = Path.cwd()


@task()
def down(ctx):
    """Docker 네트워크와 이미지를 terminate 한다."""
    ctx.run(f"docker-compose -f {REPO_ROOT / 'docker-compose.yml'} down")


@task(help={'service': '실행할 Service 명을 입력한다.'})
def up(ctx, service=None):
    """Docker 네트워크와 이미지를 데몬으로 실행한다."""
    docker_cmd = 'docker-compose up -d'

    if service is None:
        ctx.run(f"{docker_cmd}")
    else:
        ctx.run(f"{docker_cmd} {service}")


# 위에다가 task를 입력하고 아래에는 사용할 명령어 추가
ns = Collection()
ns.add_task(down)
ns.add_task(up)
