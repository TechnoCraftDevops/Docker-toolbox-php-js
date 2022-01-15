#!/bin/bash

# Data commits
CONVCOMMIT_COUNT=$(git log -s --format=%s master..totoTest  | grep --color=never -P '^(feat|fix|refactor|test)?\([a-z]*\): (.{1,50}$)' | wc -l);
INCONVCOMMIT_COUNT=$(git log -s --format=%s master..totoTest  | wc -l);

CONVCOMMIT_LIST=$(git log -s --format=%s master..totoTest  | grep -U --color=never -P '^(feat|fix|refactor|test)?\([a-z]*\): (.{1,50}$)');
INCONVCOMMIT_LIST=$(git log -s --format=%s master..totoTest );

DIFF=$(diff  <(echo -e "$INCONVCOMMIT_LIST") <(echo -e "$CONVCOMMIT_LIST"));

# Styles
red=`tput setaf 1`;
green=`tput setaf 2`;
blue=`tput setaf 4`;
yellow=`tput setaf 3`;

# whiteBg=`tput setab 8`

bold=$(tput bold);
underline=`(tput sgr 0 1)`;

reset=`tput sgr0`;

if [ ${INCONVCOMMIT_COUNT} >= 1 ];then
  echo ${green} ${bold} "all commit are conventionnals" ${reset};
  echo ${blue} "-------------" ${reset};
  echo ${green} "$CONVCOMMIT_LIST" ${reset};
  echo ${blue} "-------------" ${reset};
  echo ${bold} ${blue} "good job !" ${reset}
  exit 0;
else
  echo ${underline}${red}  "${bold}not conventionnal commit found" ${reset};
  echo ${yellow} "-------------" ${reset};
  echo ${red}"$DIFF" ${reset};
  echo ${yellow} "-------------" ${reset};
    echo ${red} "please try => ${yellow}/!\one by one commit/!\\"${rest};
  if [ ${INCONVCOMMIT_COUNT} > 1 ];then
    echo ${red} "1- ${bold}${whiteBg}git rebase -i HEAD~${INCONVCOMMIT_COUNT}"${reset};
  else
    echo ${red} "1- ${bold}${whiteBg}git rebase -i HEAD~${INCONVCOMMIT_COUNT}"${reset};
  fi
    echo ${red} "2- ${bold}${whiteBg}replace pick by edit on the commit, quit & save" ${reset};
    echo ${red} "3- run ${bold}${whiteBg} git commit --amend" ${reset};
    echo ${red} "4- ${bold}${whiteBg}rename the commit message with convientionnal format " ${reset};

    echo ${red} "5- ${bold}${whiteBg}git rebase --continue" ${reset};
    echo ${red} "${bold}${whiteBg}Et voila !" ${reset};

  echo "";
  echo ${red} "${bold}<-- don't be afraid of rebase we believe in you -->";
  exit 1;
fi