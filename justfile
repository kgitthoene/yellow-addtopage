t := "yellow-addtopage-main.zip"
s := "yellow-addtopage"

install: pack

pack:
  ( cd ..; rm {{t}}; zip --recurse-paths {{t}} {{s}} -x {{s}}/justfile* -x {{s}}'/README*.md' -x {{s}}'/example/*' -x {{s}}'/.git*' -x {{s}}'/.git/*' )
