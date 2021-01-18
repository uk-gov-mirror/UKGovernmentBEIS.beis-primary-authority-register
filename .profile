# Create the file repository configuration:
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
# Import the repository signing key:
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
# Update the package lists:
sudo apt-get update
# Install the latest version of PostgreSQL.
sudo apt-get -y install postgresql-11

export PATH=/bin:/usr/bin:/home/vcap/app/bin/pgsql/bin:/home/vcap/app/vendor/drush/drush/
source /home/vcap/app/.profile.d/finalize_bp_env_vars.sh
export TEMP=/tmp
export HOME=/home/vcap/app
