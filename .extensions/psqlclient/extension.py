# Licensed to the Apache Software Foundation (ASF) under one or more
# contributor license agreements.  See the NOTICE file distributed with
# this work for additional information regarding copyright ownership.
# The ASF licenses this file to You under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
"""Postgres Client Extension

Downloads, installs the postgres client, including pg_dump, for php.
"""
import os
import os.path
import subprocess
import logging
import shutil
from build_pack_utils.compile_extensions import CompileExtensions

_log = logging.getLogger('psqlclient')


class PostgresInstaller(object):
    def __init__(self, ctx):
        self._log = _log
        self._ctx = ctx
        self._detected = True

    def should_install(self):
        return self._detected

# Extension Methods
def preprocess_commands(ctx):
    return ()


def service_commands(ctx):
    return {}


def service_environment(ctx):
    return {}

def compile(install):
    postgres = PostgresInstaller(install.builder._ctx)
    if postgres.should_install():
        _log.info("Installing Postgres Client")
        try:
            subprocess.run(['apt-get', 'install', '-y', 'postgresql-client-12'], capture_output=True, text=True, check=True)
        except BaseException as error:
            print('Postgres Client could not be installed: '.format(error))
        _log.info("Postgres Installed.")
    return 0
