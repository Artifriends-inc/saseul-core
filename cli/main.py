from invoke import Collection, Program

from cli import docker, node, env


def run():
    program = Program(version='0.1.0')
    program.namespace = Collection()
    program.namespace.add_collection(Collection.from_module(node))
    return program.run()
