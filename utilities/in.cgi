#!/usr/bin/perl

## Set to 0 if you are using "Unique link for each account" tracking method in ToplistX
$embedded = 1;

## Change this to the "Tracking URL" setting from ToplistX
$new_in_url = 'http://www.yoursite.com/index.shtml';


##########################################################################
##                        DONE EDITING THIS FILE                        ##
##########################################################################


%q = parseget();

if( $embedded )
{
    print "Location: $new_in_url\n\n";    
}
else
{
    print "Location: $new_in_url?id=$q{'id'}\n\n";   
}


sub parseget
{
    my @pairs = split(/&/, $ENV{'QUERY_STRING'});
    my($name, $value, %q);
  
    for(@pairs)
    {
        ($name, $value) = split(/=/, $_);
        $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
        $q{$name} = $value;
    }

    return %q;
}