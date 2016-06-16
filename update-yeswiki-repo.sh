#!/bin/bash

# ##################################################
#
version="1.0.0"
#
# HISTORIQUE:
#
# * 2016.03.15 - v1.0.0  - Version initiale
#
# DEPENDANCES :
# wget, zip, unzip, md5sum
#
# ##################################################

scriptName="$(basename $BASH_SOURCE)"
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/repository" && pwd)"
# chownparam="yeswiki:users"


function mainScript() {
  # create branch repo if not exists
  if [ ! -d "${DIR}/${branch}" ];
  then
    mkdir "${DIR}/${branch}"
  fi

  if [ ! -z "${folders}" ];
  then
    if [ "${repository}" = "YesWiki/yeswiki-external-extensions" ];
    then
      if wget -q -O ${tmpDir}/yeswiki-tools.zip "https://github.com/${repository}/archive/master.zip";
      then
        success "Téléchargement de https://github.com/${repository}/archive/master.zip OK"
        cd "${tmpDir}"
        unzip -q yeswiki-tools.zip
        cd yeswiki-*
        arr=$(echo ${folders} | tr "," "\n")
        for x in $arr
        do
          cd $x
          # on cherche récursivement le dernier fichier modifié, et transßforme le timestamp de la derniere modif en date format version de yeswiki
          lastmodif="$(date +%Y-%m-%d --date=@`find . -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -f1 -d' ' | cut -f1 -d'.'`)"
          lastfound=0
          # on suppose qu'il n'y a pas plus de 10 versions par jour, on teste leur existance
          for i in 1 2 3 4 5 6 7 8 9 10
          do
            if [ -f ${DIR}/cercopitheque/tool-${x}-${lastmodif}-${i}.zip ];
            then
          	  lastfound=$(($i + 1))
            fi
          done
          if [ $lastfound -eq 0 ];
          then
            filename="tool-${x}-${lastmodif}-1.zip"
          else
            filename="tool-${x}-${lastmodif}-${lastfound}.zip"
          fi
          cd ..
          zip -rq ${filename} ${x}
          if ${clean};
          then
            # supprimer les anciennes versions
        	  rm -Rf ${DIR}/cercopitheque/tool-${x}-*
            rm -Rf ${DIR}/cercopitheque_dev/tool-${x}-*
            success "Suppression des anciennes versions du tool ${x} OK"
          fi
          cp ${filename} ${DIR}/cercopitheque/
          mv ${filename} ${DIR}/cercopitheque_dev/
          md5sum ${DIR}/cercopitheque/${filename} > ${DIR}/cercopitheque/${filename}.md5
          md5sum ${DIR}/cercopitheque_dev/${filename} > ${DIR}/cercopitheque_dev/${filename}.md5
          success "Copie de la nouvelle archive ${filename} vers les depots cercopitheque et cercopitheque_dev OK"
        done
        branch="cercopitheque"
        generatejson
        branch="cercopitheque_dev"
        generatejson
      fi
    else
      if [ "${repository}" = "YesWiki/yeswiki-themes" ];
      then
        if wget -q -O ${tmpDir}/yeswiki-themes.zip "https://github.com/${repository}/archive/master.zip";
        then
          success "Téléchargement de https://github.com/${repository}/archive/master.zip OK"
          cd "${tmpDir}"
          unzip -q yeswiki-themes.zip
          cd yeswiki-*
          arr=$(echo ${folders} | tr "," "\n")
          for x in $arr
          do
            cd $x
            # on cherche récursivement le dernier fichier modifié, et transforme le timestamp de la derniere modif en date format version de yeswiki
            lastmodif="$(date +%Y-%m-%d --date=@`find . -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -f1 -d' ' | cut -f1 -d'.'`)"
            lastfound=0
            # on suppose qu'il n'y a pas plus de 10 versions par jour, on teste leur existance
            for i in 1 2 3 4 5 6 7 8 9 10
            do
              if [ -f ${DIR}/cercopitheque/theme-${x}-${lastmodif}-${i}.zip ];
              then
            	  lastfound=$(($i + 1))
              fi
            done
            if [ $lastfound -eq 0 ];
            then
              filename="theme-${x}-${lastmodif}-1.zip"
            else
              filename="theme-${x}-${lastmodif}-${lastfound}.zip"
            fi
            cd ..
            zip -rq ${filename} ${x}
            if ${clean};
            then
              # supprimer les anciennes versions
          	  rm -Rf ${DIR}/cercopitheque/theme-${x}-*
              rm -Rf ${DIR}/cercopitheque_dev/theme-${x}-*
              success "Suppression des anciennes versions du theme ${x} OK"
            fi
            cp ${filename} ${DIR}/cercopitheque/
            mv ${filename} ${DIR}/cercopitheque_dev/
            md5sum ${DIR}/cercopitheque/${filename} > ${DIR}/cercopitheque/${filename}.md5
            md5sum ${DIR}/cercopitheque_dev/${filename} > ${DIR}/cercopitheque_dev/${filename}.md5
            success "Copie de la nouvelle archive ${filename} vers les depots cercopitheque et cercopitheque_dev OK"
          done
          branch="cercopitheque"
          generatejson
          branch="cercopitheque_dev"
          generatejson
        fi
      fi
    fi
  else
    if wget -q -O ${tmpDir}/yeswiki.zip "https://github.com/${repository}/archive/${branch}.zip";
    then
      success "Téléchargement de https://github.com/${repository}/archive/${branch}.zip OK"
      cd "${tmpDir}"
      unzip -q yeswiki.zip
      cd yeswiki-*
      # on cherche récursivement le dernier fichier modifié, et transßforme le timestamp de la derniere modif en date format version de yeswiki
      lastmodif="$(date +%Y-%m-%d --date=@`find . -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -f1 -d' ' | cut -f1 -d'.'`)"
      lastfound=0
      # on suppose qu'il n'y a pas plus de 10 versions par jour, on teste leur existance
      for i in 1 2 3 4 5 6 7 8 9 10
      do
        if [ -f ${DIR}/${branch}/yeswiki-${branch}-${lastmodif}-${i}.zip ];
        then
      	  lastfound=$(($i + 1))
        fi
      done
      if [ $lastfound -eq 0 ];
      then
        filename="yeswiki-${branch}-${lastmodif}-1.zip"
      else
        filename="yeswiki-${branch}-${lastmodif}-${lastfound}.zip"
      fi

      zip -rq ${filename} *
      if ${clean};
      then
        # supprimer les anciennes versions
    	  rm -Rf ${DIR}/${branch}/yeswiki-${branch}-*
        success "Suppression des anciennes versions de yeswiki OK"
      fi
      mv ${filename} ${DIR}/${branch}/
      md5sum ${DIR}/${branch}/${filename} > ${DIR}/${branch}/${filename}.md5
      success "Nouvelle archive ${filename} OK"
    fi
    # on met les droits pour le repository au bon user et group
    # chown ${chownparam} ${DIR}/${branch}/* -R
    generatejson
  fi
}

function generatejson() {
  # generatejson Function
  # -----------------------------------
  # generation du fichier json contenant les infos sur les fichiers.
  # -----------------------------------
  cd ${DIR}/${branch}/
  json="{"
  # derniere release
  lastrelease="$(ls -r1 yeswiki-${branch}-2*.zip 2>/dev/null | head -1 || echo 'Pas de release')"
  if [ ! "${lastrelease}" = "Pas de release" ];
  then
    versionrelease="${lastrelease/yeswiki-${branch}-/}"
    versionrelease="${versionrelease/.zip/}"
    json="${json}
    \"yeswiki\": {
      \"version\": \"${versionrelease}\",
      \"file\": \"${lastrelease}\"
    }"
  fi

  # themes
  themes=($(ls theme-*.zip 2>/dev/null || echo 'Pas de themes' ))
  if [ ! "${themes}" = "Pas de themes" ];
  then
    # y a t'il deja des données? Dans ce cas on met une virgule
    if [ ! "${json}" = "{" ];
    then
      json="${json},"
    fi
    first=true
    for i in "${themes[@]}"
    do
      themever="${i/.zip/}"
      IFS='-' read -r prefix theme version <<< "$themever"
      if [ "$first" = false ];
      then
        json="${json},"
      else
        first=false
      fi
    	json="${json}
    \"${prefix}-${theme}\": {
      \"version\": \"${version}\",
      \"file\": \"${i}\"
    }"
    done
  fi

  # tools
  tools=($(ls tool-*.zip 2>/dev/null || echo 'Pas de tools'))
  if [ ! "${tools}" = "Pas de tools" ];
  then
    # y a t'il deja des données? Dans ce cas on met une virgule
    if [ ! "${json}" = "{" ];
    then
      json="${json},"
    fi
    first=true
    for i in "${tools[@]}"
    do
      tool="${i/.zip/}"
      IFS='-' read -r prefix tool version <<< "$tool"
      if [ "$first" = false ];
      then
        json="${json},"
      else
        first=false
      fi
    	json="${json}
    \"${prefix}-${tool}\": {
      \"version\": \"${version}\",
      \"file\": \"${i}\"
    }"
    done
  fi

  # fermeture du json
  json="${json}
}"
  echo "${json}" > ${DIR}/${branch}/packages.json
  success "Mise à jour du fichier ${DIR}/${branch}/packages.json OK"
  echo -n

}

function trapCleanup() {
  # trapCleanup Function
  # -----------------------------------
  # Any actions that should be taken if the script is prematurely
  # exited.  Always call this function at the top of your script.
  # -----------------------------------
  echo ""
  # Delete temp files, if any
  if [ -d "${tmpDir}" ] ; then
    rm -r "${tmpDir}"
  fi
  die "Exit trapped."
}

function safeExit() {
  # safeExit
  # -----------------------------------
  # Non destructive exit for when script exits naturally.
  # Usage: Add this function at the end of every script.
  # -----------------------------------
  # Delete temp files, if any
  if [ -d "${tmpDir}" ] ; then
    rm -r "${tmpDir}"
  fi
  trap - INT TERM EXIT
  exit
}

# Set Flags
# -----------------------------------
# Flags which can be overridden by user input.
# Default values are below
# -----------------------------------
quiet=false
printLog=false
verbose=false
force=false
strict=false
debug=false
clean=false
args=()

# Set Temp Directory
# -----------------------------------
# Create temp directory with three random numbers and the process ID
# in the name.  This directory is removed automatically at exit.
# -----------------------------------
tmpDir="/home/yeswiki/www/tmp/${scriptName}.$RANDOM.$RANDOM.$RANDOM.$$"
(umask 077 && mkdir "${tmpDir}") || {
  die "Could not create temporary directory! Exiting."
}

# Logging
# -----------------------------------
# Log is only used when the '-l' flag is set.
#
# To never save a logfile change variable to '/dev/null'
# Save to Desktop use: $HOME/Desktop/${scriptBasename}.log
# Save to standard user log location use: $HOME/Library/Logs/${scriptBasename}.log
# -----------------------------------
logFile="${DIR}/${scriptBasename}.log"


# Options and Usage
# -----------------------------------
# Print usage
usage() {
  echo -n "

${scriptName} [OPTION]...

Ce script génère une archive à partir d'un dépot indiqué à la date du jour.

 ${bold}Options:${reset}
  -r, --repository  Dépot utilisé
  -b, --branch      Branche utilisée
  -t, --tag         Tag utilisé
  -c, --clean       Efface les archives plus anciennes
  -f, --folders     Listes des dossiers a extraite en zip, séparés par une virgule
  --force           Sans intéractions de l'utilisateur. 'Oui' à toutes les actions.
  -q, --quiet       Mode silencieux
  -l, --log         Création d'un fichier log
  -s, --strict      Mode strict, sort en cas de variable non initialisée
  -v, --verbose     Mode verbeux
  -d, --debug       Mode debug
  -h, --help        Afficher l'aide
      --version     Affiche le numéro de version et sort
"
}

# Iterate over options breaking -ab into -a -b when needed and --foo=bar into
# --foo bar
optstring=h
unset options
while (($#)); do
  case $1 in
    # If option is of type -ab
    -[!-]?*)
      # Loop over each character starting with the second
      for ((i=1; i < ${#1}; i++)); do
        c=${1:i:1}

        # Add current char to options
        options+=("-$c")

        # If option takes a required argument, and it's not the last char make
        # the rest of the string its argument
        if [[ $optstring = *"$c:"* && ${1:i+1} ]]; then
          options+=("${1:i+1}")
          break
        fi
      done
      ;;

    # If option is of type --foo=bar
    --?*=*) options+=("${1%%=*}" "${1#*=}") ;;
    # add --endopts for --
    --) options+=(--endopts) ;;
    # Otherwise, nothing special
    *) options+=("$1") ;;
  esac
  shift
done
set -- "${options[@]}"
unset options

# Print help if no arguments were passed.
# Uncomment to force arguments when invoking the script
# -------------------------------------
[[ $# -eq 0 ]] && set -- "--help"

# Read the options and set stuff
while [[ $1 = -?* ]]; do
  case $1 in
    -h|--help) usage >&2; safeExit ;;
    --version) echo "$(basename $0) ${version}"; safeExit ;;
    -r|--repository) shift; repository=${1} ;;
    -b|--branch) shift; branch=${1} ;;
    -f|--folders) shift; folders=${1} ;;
    -t|--tag) shift; tag=${1} ;;
    -c|--clean) clean=true ;;
    -v|--verbose) verbose=true ;;
    -l|--log) printLog=true ;;
    -q|--quiet) quiet=true ;;
    -s|--strict) strict=true;;
    -d|--debug) debug=true;;
    --force) force=true ;;
    --endopts) shift; break ;;
    *) die "invalid option: '$1'." ;;
  esac
  shift
done

# Store the remaining part as arguments.
args+=("$@")


# Logging and Colors
# -----------------------------------------------------
# Here we set the colors for our script feedback.
# Example usage: success "sometext"
#------------------------------------------------------

# Set Colors
bold=$(tput bold)
reset=$(tput sgr0)
purple=$(tput setaf 171)
red=$(tput setaf 1)
green=$(tput setaf 76)
tan=$(tput setaf 3)
blue=$(tput setaf 38)
underline=$(tput sgr 0 1)

function _alert() {
  if [ "${1}" = "emergency" ]; then local color="${bold}${red}"; fi
  if [ "${1}" = "error" ] || [ "${1}" = "warning" ]; then local color="${red}"; fi
  if [ "${1}" = "success" ]; then local color="${green}"; fi
  if [ "${1}" = "debug" ]; then local color="${purple}"; fi
  if [ "${1}" = "header" ]; then local color="${bold}""${tan}"; fi
  if [ "${1}" = "input" ]; then local color="${bold}"; printLog="0"; fi
  if [ "${1}" = "info" ] || [ "${1}" = "notice" ]; then local color=""; fi
  # Don't use colors on pipes or non-recognized terminals
  if [[ "${TERM}" != "xterm"* ]] || [ -t 1 ]; then color=""; reset=""; fi

  # Print to $logFile
  if ${printLog}; then
    echo -e "$(date +"%m-%d-%Y %r") $(printf "[%9s]" "${1}") ${_message}" >> "${logFile}";
  fi

  # Print to console when script is not 'quiet'
  if ${quiet}; then
   return
  else
   echo -e "$(date +"%r") ${color}$(printf "[%9s]" "${1}") ${_message}${reset}";
  fi
}

function die ()       { local _message="${*} Exiting."; echo "$(_alert emergency)"; safeExit;}
function error ()     { local _message="${*}"; echo "$(_alert error)"; }
function warning ()   { local _message="${*}"; echo "$(_alert warning)"; }
function notice ()    { local _message="${*}"; echo "$(_alert notice)"; }
function info ()      { local _message="${*}"; echo "$(_alert info)"; }
function debug ()     { local _message="${*}"; echo "$(_alert debug)"; }
function success ()   { local _message="${*}"; echo "$(_alert success)"; }
function input()      { local _message="${*}"; echo -n "$(_alert input)"; }
function header()     { local _message="========== ${*} ==========  "; echo "$(_alert header)"; }

# Log messages when verbose is set to "true"
verbose() { if ${verbose}; then debug "$@"; fi }

# Trap bad exits with your cleanup function
trap trapCleanup EXIT INT TERM

# Set IFS to preferred implementation
IFS=$'\n\t'

# Exit on error. Append '||true' when you run the script if you expect an error.
set -o errexit

# Run in debug mode, if set
if ${debug}; then set -x ; fi

# Exit on empty variable
if ${strict}; then set -o nounset ; fi

# Bash will remember & return the highest exitcode in a chain of pipes.
# This way you can catch the error in case mysqldump fails in `mysqldump |gzip`, for example.
set -o pipefail

# Run your script
mainScript

# Exit cleanly
safeExit
