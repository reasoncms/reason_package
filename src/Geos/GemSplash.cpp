////////////////////////////////////////////////////////
//
// GEM - Graphics Environment for Multimedia
//
// zmoelnig@iem.kug.ac.at
//
// Implementation file
//
//    Copyright (c) 1997-2000 Mark Danks.
//    Copyright (c) G�nther Geiger.
//    Copyright (c) 2001-2002 IOhannes m zmoelnig. forum::f�r::uml�ute. IEM
//    For information on usage and redistribution, and for a DISCLAIMER OF ALL
//    WARRANTIES, see the file, "GEM.LICENSE.TERMS" in this distribution.
//
/////////////////////////////////////////////////////////

#include "GemSplash.h"
#include "Base/GemState.h"

CPPEXTERN_NEW(GemSplash)

/////////////////////////////////////////////////////////
//
// GemSplash
//
/////////////////////////////////////////////////////////
// Constructor
//
/////////////////////////////////////////////////////////
GemSplash :: GemSplash()
{
}

/////////////////////////////////////////////////////////
// Destructor
//
/////////////////////////////////////////////////////////
GemSplash :: ~GemSplash()
{ }

/////////////////////////////////////////////////////////
// render
//
/////////////////////////////////////////////////////////
void GemSplash :: render(GemState *state)
{
  // this should do something cool.
  // and it should display "Gem" and the version-number
  // probably the core people that were involved
  // should be mentioned too

  // probably we should do a GemSplash contest
}
 
/////////////////////////////////////////////////////////
// static member function
//
/////////////////////////////////////////////////////////
void GemSplash :: obj_setupCallback(t_class *classPtr)
{
  class_addcreator((t_newmethod)create_GemSplash, gensym("Gem"), A_NULL); 
}
