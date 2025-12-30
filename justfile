t := "yellow-addtopage-main.zip"
s := "yellow-addtopage"

install: pack

pack:
  ( cd ..; rm {{t}}; zip --recurse-paths {{t}} {{s}} -x {{s}}/justfile.deploy )
