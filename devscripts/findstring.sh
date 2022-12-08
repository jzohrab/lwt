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
    echo "$SEARCHFOR IN $1"
    echo
    find . -name '*.*' -maxdepth $2 -print0 | xargs -0 grep "$SEARCHFOR" 2>/dev/null | grep -v vendor | grep -v docs | grep -v ^\./js/ | grep -v "Binary file" | grep -v ^\./themes/ | grep -v src/themes | grep -v src/css | grep -v ^\./css
    popd > /dev/null
    echo
}

runsearch . 1

runsearch inc 1

runsearch public 8

runsearch tests 8
