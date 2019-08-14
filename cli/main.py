from invoke import Collection, Program

from cli import docker, node


def run():
    program = Program(version='0.1.0')
    program.namespace = Collection()
    program.namespace.add_collection(Collection.from_module(node))
    program.namespace.add_collection(Collection.from_module(docker))
    return program.run()
