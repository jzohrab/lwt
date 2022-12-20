args=("$@")
if [[ "$#" -eq "0" ]]; then
    echo "Need search string."
fi;
echo "Searching for $@ (skipping themes, css) ..."
echo

SEARCHFOR="$@"

function runsearch() {
    pushd $1 > /dev/null
    echo
    echo "$SEARCHFOR IN $1 at depth $2"
    echo
    find . -name '*.*' -maxdepth $2 -print0 | xargs -0 grep "$SEARCHFOR" 2>/dev/null | grep -v .min.js | grep -v phpunit.result.cache | grep -v findstring.sh | grep -v composer.json
    popd > /dev/null
    echo
}

runsearch . 1

runsearch public 8

runsearch config 8

runsearch src 8

runsearch templates 8

runsearch tests 8
