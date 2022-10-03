#!/bin/sh

# Set Colors
bold=$(tput bold)
underline=$(tput sgr 0 1)
reset=$(tput sgr0)
purple=$(tput setaf 171)
red=$(tput setaf 1)
green=$(tput setaf 76)
tan=$(tput setaf 3)
blue=$(tput setaf 38)

# Headers and  Logging
e_header() {
  printf "\n${bold}${purple}==========  %s  ==========${reset}\n" "$@"
}
e_arrow() {
  printf "➜ $@\n"
}
e_success() {
  printf "${green}✔ %s${reset}\n" "$@"
}
e_error() {
  printf "${red}✖ %s${reset}\n" "$@"
}
e_warning() {
  printf "${tan}➜ %s${reset}\n" "$@"
}
e_underline() {
  printf "${underline}${bold}%s${reset}\n" "$@"
}
e_bold() {
  printf "${bold}%s${reset}\n" "$@"
}
e_note() {
  printf "${underline}${bold}${blue}Note:${reset}  ${blue}%s${reset}\n" "$@"
}

type_exists() {
  if [ $(command -v $1) ]; then
    return 0
  fi
  return 1
}

init_docker_hooks() {
  # Git hooks folder
  GIT_HOOKS_FOLDER=.git/hooks
  ### Git hooks ####
  if [ -d "$GIT_HOOKS_FOLDER" ]; then
    # Post merge hook.
    cp ci/local/tools/post-merge .git/hooks/post-merge
    cp ci/local/tools/pre-commit .git/hooks/pre-commit
    # Make files executable.
    # Used only for dev env
    chmod +x .git/hooks/pre-commit .git/hooks/post-merge
  fi
  ### End Git hooks ####
}

init_native_hooks() {
   ### Create Codesniffer symlink ###
  # Target file.
  TARGET=$PWD/vendor/drupal/coder/coder_sniffer/Drupal
  TARGET_DP=$PWD/vendor/drupal/coder/coder_sniffer/DrupalPractice
  # Link name.
  LINK_NAME=vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/Drupal
  LINK_NAME_DP=vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/DrupalPractice
  # Link folder.
  LINK_FOLDER=vendor/squizlabs/php_codesniffer/CodeSniffer/Standards
  # Git hooks folder
  GIT_HOOKS_FOLDER=.git/hooks

  mkdir -p "$LINK_FOLDER"

  if [ -d "$LINK_FOLDER" ]; then
    # Create symlink Drupal standard.
    ln -sf ${TARGET} ${LINK_NAME}
    echo "link created" ${TARGET} ${LINK_NAME}

    # Creates symlink to DrupalPractice.
    ln -sf ${TARGET_DP} ${LINK_NAME_DP}

    ### Git hooks ####
    if [ -d "$GIT_HOOKS_FOLDER" ]; then
      # Pre commit hook
      cp ci/local/tools/pre-commit-native .git/hooks/pre-commit
      # Make files executable.
      chmod +x .git/hooks/pre-commit
    fi
    ### End Git hooks ####
  fi
  ### End Create symlink ####

  ### Git hooks ####
  if [ -d "$GIT_HOOKS_FOLDER" ]; then
    # Post merge hook.
    cp ci/local/tools/post-merge-native .git/hooks/post-merge
    # Make files executable.
    # Used only for dev env.
    chmod +x .git/hooks/post-merge
  fi
  ### End Git hooks ####
}

if [ -f /.dockerenv ];
  then
    init_docker_hooks
  else
    # Check for docker
    if type_exists 'docker';
      then
        e_success "docker good to go"
        # init hooks if docker is available
        if (! docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}" );
          then
            e_error "Docker does not seem to be running, run it first and retry"
          else
            init_docker_hooks
        fi
      else
        init_native_hooks
    fi
fi
