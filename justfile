t := "yellow-addtopage-main.zip"
s := "yellow-addtopage"

install: pack

pack:
  cp addtopage.php extension.ini dist/.
  git archive --format=zip --output=main.zip ./dist
