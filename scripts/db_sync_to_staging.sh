rsync -Pav -e "ssh -i ~/.ssh/wfo-aws-03.pem" --delete ../data/db_dumps wfo@wfo-staging.rbge.info:/var/wfo-list/rhakhis/api/data/