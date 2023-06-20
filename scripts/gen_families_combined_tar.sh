# This will billed a tar file of all the DwC families files 
# ../www/downloads/dwc/families_dwc.tar.gz

# remove the old one
rm -rfs ../www/downloads/dwc/families_dwc.tar.gz

# recreate it being careful not to add the _Uber files
tar -czvf ../www/downloads/dwc/families_dwc.tar.gz  ../www/downloads/dwc/*_wfo-*.zip

